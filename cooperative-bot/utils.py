import re
from datetime import datetime
import emoji

# ML-light approach with multiple regex patterns for transaction parsing
PATTERNS = {
    'amount': [
        r'[₹$€Rs\.]\s*([\d,]+\.?\d*)', 
        r'amount\s*[:\s]*([\d,]+\.?\d*)',
        r'(?:Rs\.?|INR)\s*([\d,]+\.?\d*)',
        r'credited\s+with\s+(?:Rs\.?|INR)?\s*([\d,]+\.?\d*)'
    ],
    'payer': [
        r'received from\s+([A-Za-z\s.]+)',
        r'paid by\s+([A-Za-z\s.]+)',
        r'from\s+([A-Za-z\s.]+?)(?:\s|\(|$)',
        r'UPI\s+([A-Za-z\s.]+)',
        r'by\s+([A-Za-z\s.]+?)(?:\s|\(|$)',
    ],
    'reference': [
        r'UTR\s*[:\s]*(\d+)', 
        r'Reference\s*[:\s]*([A-Z0-9]+)',
        r'Ref(?:\.|\sNo\.?)?\s*[:\s]*([A-Z0-9]+)'
    ]
}

def extract_with_patterns(text, patterns):
    for pattern in patterns:
        match = re.search(pattern, text, re.IGNORECASE)
        if match:
            # Return the first capture group, stripped of whitespace
            return match.group(1).strip()
    return None

def extract_transaction_details(subject, body):
    full_text = f"{subject}\n{body}"
    
    amount_str = extract_with_patterns(full_text, PATTERNS['amount'])
    payer = extract_with_patterns(full_text, PATTERNS['payer'])
    reference = extract_with_patterns(full_text, PATTERNS['reference'])
    
    amount = 0.0
    if amount_str:
        # Clean up the amount string (remove commas) and convert to float
        amount_clean = amount_str.replace(',', '')
        try:
            amount = float(amount_clean)
        except ValueError:
            pass
            
    return {
        'amount': amount,
        'payer': payer if payer else "Unknown",
        'reference': reference if reference else "N/A"
    }

def format_currency(amount):
    return f"₹{amount:,.2f}"

def format_date(dt_obj):
    return dt_obj.strftime("%d %b %Y, %I:%M %p")

def generate_monthly_history_text(year, summary):
    text = f"📊 *{year} Monthly Transaction History*\n\n"
    text += "┌──────────────────────────────────────┐\n"
    text += "│ Month      Amount    Count  Members │\n"
    text += "├──────────────────────────────────────┤\n"
    
    months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]
    
    total_amount = 0
    total_count = 0
    all_members = set()
    
    best_month = None
    best_amount = -1
    lowest_month = None
    lowest_amount = float('inf')
    
    for month_idx in range(1, 13):
        m_name = months[month_idx - 1]
        m_data = summary.get(month_idx, {'amount': 0.0, 'count': 0, 'payers': set()})
        
        amt = m_data['amount']
        cnt = m_data['count']
        mems = len(m_data['payers'])
        
        total_amount += amt
        total_count += cnt
        all_members.update(m_data['payers'])
        
        if cnt > 0:
            if amt > best_amount:
                best_amount = amt
                best_month = m_name
            if amt < lowest_amount:
                lowest_amount = amt
                lowest_month = m_name
                
        # Format row: 10 chars, 9 chars, 7 chars, 7 chars
        amt_str = f"₹{int(amt/1000)}k" if amt >= 1000 else f"₹{int(amt)}"
        text += f"│ {m_name:<9} {amt_str:<9} {cnt:<6} {mems:<7} │\n"

    text += "├──────────────────────────────────────┤\n"
    tot_amt_str = f"₹{int(total_amount/1000)}k" if total_amount >= 1000 else f"₹{int(total_amount)}"
    text += f"│ TOTAL      {tot_amt_str:<9} {total_count:<6} {len(all_members):<7}*│\n"
    text += "└──────────────────────────────────────┘\n\n"
    
    text += "📈 *Trend Analysis:*\n"
    if best_month:
        text += f"🔼 Best Month: {best_month} ({format_currency(best_amount)})\n"
    if lowest_month and lowest_amount != float('inf'):
        text += f"📉 Lowest Month: {lowest_month} ({format_currency(lowest_amount)})\n"
        
    avg_monthly = total_amount / 12 if total_amount > 0 else 0
    text += f"📊 Average Monthly: {format_currency(avg_monthly)}\n\n"
    text += "*Unique members across year"
    
    return text

def parse_month_year(text):
    """
    Parses strings like "January 2024", "01-2024", "Jan 2024"
    Returns (month_int, year_int) or (None, None) if invalid.
    """
    text = text.strip().lower()
    
    # Try MM-YYYY
    match = re.match(r'^(\d{1,2})[-/](\d{4})$', text)
    if match:
        m, y = int(match.group(1)), int(match.group(2))
        if 1 <= m <= 12:
            return m, y
            
    # Try Month YYYY
    months = ["january", "february", "march", "april", "may", "june", "july", "august", "september", "october", "november", "december"]
    short_months = ["jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec"]
    
    match = re.match(r'^([a-z]+)\s+(\d{4})$', text)
    if match:
        m_str, y_str = match.group(1), match.group(2)
        y = int(y_str)
        if m_str in months:
            return months.index(m_str) + 1, y
        if m_str in short_months:
            return short_months.index(m_str) + 1, y
            
    return None, None
