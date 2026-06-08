import os
import logging
import asyncio
import threading
from datetime import datetime, timedelta
import pytz
from flask import Flask

from telegram import Update
from telegram.ext import (
    Application, CommandHandler, ContextTypes, 
    MessageHandler, filters
)

from config import Config
from database import db
from gmail_parser import gmail_parser
from utils import (
    format_currency, format_date, generate_monthly_history_text, parse_month_year
)
from receipt_generator import generate_receipt
from chart_generator import generate_monthly_chart
from sheets_sync import sheets_sync

# Enable logging
logging.basicConfig(
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s", level=logging.INFO
)
logger = logging.getLogger(__name__)

# Flask App for Health Check
app = Flask(__name__)
last_email_check = None

@app.route('/health')
def health():
    return {"status": "healthy", "timestamp": datetime.now().isoformat()}, 200

@app.route('/metrics')
def metrics():
    return {
        "transactions": db.get_transaction_count(),
        "total_amount": db.get_total_amount(),
        "last_check": last_email_check.isoformat() if last_email_check else None
    }

def run_flask():
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=False, use_reloader=False)

# Access Control Decorator
def admin_only(func):
    async def wrapper(update: Update, context: ContextTypes.DEFAULT_TYPE):
        if Config.ADMIN_ID and update.effective_user.id != Config.ADMIN_ID:
            await update.message.reply_text("⛔ You are not authorized to use this command.")
            return
        return await func(update, context)
    return wrapper

# --- Bot Commands ---

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    welcome_msg = (
        "🤖 *Welcome to the Cooperative Society Bot!*\n\n"
        "I monitor payments and maintain the ledger.\n"
        "Type /help to see all available commands."
    )
    await update.message.reply_text(welcome_msg, parse_mode="Markdown")

async def help_command(update: Update, context: ContextTypes.DEFAULT_TYPE):
    help_text = """
*Available Commands:*

*Queries:*
/recent [n] - Show last n transactions
/today - Show today's transactions
/yesterday - Show yesterday's transactions
/last24h - Transactions in last 24 hours
/last7days - Weekly summary
/monthly [MM-YYYY] - Month's transactions
/monthly_history [YYYY] - Year breakdown
/search @name - Search by member
/search_amount [min] [max] - Search by amount

*Reports & Visuals:*
/summary - Financial summary
/report - Get CSV of recent transactions
/monthly_report [MM-YYYY] - Monthly CSV
/yearly_report [YYYY] - Yearly CSV
/export_all - Export full database
/top_payers [n] - Show top contributors
/chart - Visual trend of collections

*Members & Expenses:*
/members - List all members
/member_total @name - Show member total
/pending - List unpaid members this month
/net_balance - Show collections vs expenses
"""
    if Config.ADMIN_ID and update.effective_user.id == Config.ADMIN_ID:
        help_text += "\n*Admin:*\n/add_expense [amt] [desc]\n/broadcast [msg]\n/add_manual name amt date\n/correct id amt\n"

    await update.message.reply_text(help_text, parse_mode="Markdown")

async def recent(update: Update, context: ContextTypes.DEFAULT_TYPE):
    try:
        limit = int(context.args[0]) if context.args else 10
    except ValueError:
        limit = 10
        
    txs = db.get_recent(limit)
    if not txs:
        await update.message.reply_text("No transactions found.")
        return
        
    msg = f"📝 *Last {limit} Transactions:*\n\n"
    for t in txs:
        msg += f"• {format_date(t.time)} - {format_currency(t.amount)} by {t.payer}\n"
    await update.message.reply_text(msg, parse_mode="Markdown")

async def monthly(update: Update, context: ContextTypes.DEFAULT_TYPE):
    tz = pytz.timezone(Config.TIMEZONE)
    now = datetime.now(tz)
    
    if context.args:
        month, year = parse_month_year(" ".join(context.args))
        if not month:
            await update.message.reply_text("Invalid format. Use MM-YYYY (e.g. 01-2024) or Month YYYY (e.g. Jan 2024)")
            return
    else:
        month = now.month
        year = now.year
        
    txs = db.get_transactions_by_month(year, month)
    
    month_name = datetime(year, month, 1).strftime('%B')
    
    total = sum(t.amount for t in txs)
    count = len(txs)
    unique_payers = set(t.payer.lower() for t in txs)
    avg = total / count if count > 0 else 0
    
    msg = f"📅 *{month_name} {year} - Transaction History*\n\n"
    msg += "┌─────────────────────────────────┐\n"
    msg += f"│ Total Collected: {format_currency(total):<14} │\n"
    msg += f"│ Total Transactions: {count:<11} │\n"
    msg += f"│ Average Payment: {format_currency(avg):<14} │\n"
    msg += f"│ Unique Members: {len(unique_payers):<15} │\n"
    msg += "└─────────────────────────────────┘\n\n"
    
    if count > 0:
        msg += "📊 *Daily Breakdown (Top 5):*\n"
        # Group by day
        daily = {}
        for t in txs:
            day = t.time.strftime("%b %d")
            if day not in daily:
                daily[day] = {'amt': 0, 'cnt': 0}
            daily[day]['amt'] += t.amount
            daily[day]['cnt'] += 1
            
        # Sort and show top 5 days
        sorted_days = sorted(daily.items(), key=lambda x: x[0])[:5]
        for day, data in sorted_days:
            msg += f"{day}: {format_currency(data['amt'])} ({data['cnt']} payments)\n"
            
    await update.message.reply_text(msg, parse_mode="Markdown")

async def monthly_history(update: Update, context: ContextTypes.DEFAULT_TYPE):
    tz = pytz.timezone(Config.TIMEZONE)
    year = int(context.args[0]) if context.args else datetime.now(tz).year
    
    summary = db.get_monthly_summary(year)
    msg = generate_monthly_history_text(year, summary)
    
    await update.message.reply_text(msg, parse_mode="Markdown")

async def summary(update: Update, context: ContextTypes.DEFAULT_TYPE):
    total = db.get_total_amount()
    count = db.get_transaction_count()
    payers = db.get_unique_payers()
    avg = total / count if count > 0 else 0
    
    msg = (
        "📈 *Financial Summary*\n\n"
        f"Total Collected: {format_currency(total)}\n"
        f"Total Transactions: {count}\n"
        f"Average Payment: {format_currency(avg)}\n"
        f"Total Members: {len(payers)}"
    )
    await update.message.reply_text(msg, parse_mode="Markdown")

async def monthly_report(update: Update, context: ContextTypes.DEFAULT_TYPE):
    tz = pytz.timezone(Config.TIMEZONE)
    if context.args:
        month, year = parse_month_year(" ".join(context.args))
        if not month:
            await update.message.reply_text("Invalid format. Use MM-YYYY or Month YYYY")
            return
    else:
        now = datetime.now(tz)
        month, year = now.month, now.year
        
    filename = db.export_monthly_report(year, month)
    
    await context.bot.send_document(
        chat_id=update.effective_chat.id,
        document=open(filename, 'rb'),
        caption=f"Monthly Report for {month}/{year}"
    )
    os.remove(filename)

async def export_all(update: Update, context: ContextTypes.DEFAULT_TYPE):
    filename = db.export_to_csv()
    await context.bot.send_document(
        chat_id=update.effective_chat.id,
        document=open(filename, 'rb'),
        caption="Full Database Export"
    )
    os.remove(filename)

async def chart(update: Update, context: ContextTypes.DEFAULT_TYPE):
    tz = pytz.timezone(Config.TIMEZONE)
    year = int(context.args[0]) if context.args else datetime.now(tz).year
    
    summary = db.get_monthly_summary(year)
    if not any(data['count'] > 0 for data in summary.values()):
        await update.message.reply_text(f"No transactions found in {year} to plot.")
        return
        
    await update.message.reply_text("Generating chart... 📊")
    filename = generate_monthly_chart(summary, year)
    
    await context.bot.send_photo(
        chat_id=update.effective_chat.id,
        photo=open(filename, 'rb'),
        caption=f"Collection Trends for {year}"
    )
    os.remove(filename)

async def pending(update: Update, context: ContextTypes.DEFAULT_TYPE):
    tz = pytz.timezone(Config.TIMEZONE)
    now = datetime.now(tz)
    
    pending_list = db.get_pending_payers(now.year, now.month, Config.MONTHLY_CONTRIBUTION_AMOUNT)
    
    if not pending_list:
        await update.message.reply_text("🎉 Everyone has met their monthly contribution target!")
        return
        
    msg = f"⏳ *Pending Payments for {now.strftime('%B %Y')}*\n"
    msg += f"Target: {format_currency(Config.MONTHLY_CONTRIBUTION_AMOUNT)}\n\n"
    
    for p in pending_list:
        msg += f"• {p['payer']}: Due {format_currency(p['due'])}\n"
        
    await update.message.reply_text(msg, parse_mode="Markdown")

async def net_balance(update: Update, context: ContextTypes.DEFAULT_TYPE):
    total_in = db.get_total_amount()
    total_out = db.get_total_expenses()
    net = total_in - total_out
    
    msg = (
        "💼 *Net Balance Report*\n\n"
        f"🟢 Total Collections: {format_currency(total_in)}\n"
        f"🔴 Total Expenses: {format_currency(total_out)}\n"
        "─────────────────\n"
        f"🏦 *Net Balance:* {format_currency(net)}"
    )
    await update.message.reply_text(msg, parse_mode="Markdown")

@admin_only
async def add_expense(update: Update, context: ContextTypes.DEFAULT_TYPE):
    if len(context.args) < 2:
        await update.message.reply_text("Usage: /add_expense [amount] [description]")
        return
        
    try:
        amount = float(context.args[0])
        description = " ".join(context.args[1:])
    except ValueError:
        await update.message.reply_text("Invalid amount.")
        return
        
    expense_id = db.add_expense(amount, description)
    if expense_id:
        await update.message.reply_text(f"✅ Added expense of {format_currency(amount)} for '{description}'.")
    else:
        await update.message.reply_text("Failed to add expense.")

# --- Background Tasks ---

async def poll_gmail(context: ContextTypes.DEFAULT_TYPE):
    global last_email_check
    logger.info("Polling Gmail for new payments...")
    last_email_check = datetime.now()
    
    new_txs = gmail_parser.fetch_new_payments(minutes_ago=15)
    
    for tx in new_txs:
        # 1. Sync to Google Sheets
        sheets_sync.append_transaction(tx)
        
        # 2. Generate PDF Receipt
        receipt_filename = generate_receipt(tx)
        
        # 3. Notify the admin or group
        msg = (
            "💰 *New Payment Detected!*\n\n"
            f"Amount: {format_currency(tx['amount'])}\n"
            f"From: {tx['payer']}\n"
            f"Ref: {tx['reference']}\n"
            f"Time: {format_date(tx['time'])}"
        )
        if tx['amount'] > 10000:
            msg = "🎉🎉 *LARGE PAYMENT!* 🎉🎉\n" + msg
            
        if Config.ADMIN_ID:
            try:
                await context.bot.send_document(
                    chat_id=Config.ADMIN_ID,
                    document=open(receipt_filename, 'rb'),
                    caption=msg,
                    parse_mode="Markdown"
                )
            except Exception as e:
                logger.error(f"Failed to send notification: {e}")
                
        os.remove(receipt_filename)

def main():
    # Start Flask Server in background
    flask_thread = threading.Thread(target=run_flask)
    flask_thread.daemon = True
    flask_thread.start()

    # Create Telegram Application
    application = Application.builder().token(Config.TELEGRAM_BOT_TOKEN).build()

    # Add Handlers
    application.add_handler(CommandHandler("start", start))
    application.add_handler(CommandHandler("help", help_command))
    application.add_handler(CommandHandler("recent", recent))
    application.add_handler(CommandHandler("monthly", monthly))
    application.add_handler(CommandHandler("monthly_history", monthly_history))
    application.add_handler(CommandHandler("summary", summary))
    application.add_handler(CommandHandler("monthly_report", monthly_report))
    application.add_handler(CommandHandler("export_all", export_all))
    
    # Advanced features handlers
    application.add_handler(CommandHandler("chart", chart))
    application.add_handler(CommandHandler("pending", pending))
    application.add_handler(CommandHandler("net_balance", net_balance))
    application.add_handler(CommandHandler("add_expense", add_expense))

    # Job Queue for polling
    job_queue = application.job_queue
    job_queue.run_repeating(poll_gmail, interval=900, first=10) # Run every 15 mins

    logger.info("Starting bot...")
    application.run_polling(allowed_updates=Update.ALL_TYPES)

if __name__ == "__main__":
    main()
