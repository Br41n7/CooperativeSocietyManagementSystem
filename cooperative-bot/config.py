import os
from dotenv import load_dotenv

# Load environment variables from .env file
load_dotenv()

class Config:
    # Telegram
    TELEGRAM_BOT_TOKEN = os.getenv("TELEGRAM_BOT_TOKEN")
    
    ADMIN_ID = os.getenv("ADMIN_ID")
    if ADMIN_ID:
        try:
            ADMIN_ID = int(ADMIN_ID)
        except ValueError:
            pass

    # Database
    DATABASE_URL = os.getenv("DATABASE_URL", "sqlite:///cooperative.db")
    # SQLAlchemy requires postgresql:// instead of postgres:// 
    if DATABASE_URL.startswith("postgres://"):
        DATABASE_URL = DATABASE_URL.replace("postgres://", "postgresql://", 1)

    # General
    TIMEZONE = os.getenv("TIMEZONE", "Asia/Kolkata")
    MONTHLY_CONTRIBUTION_AMOUNT = float(os.getenv("MONTHLY_CONTRIBUTION_AMOUNT", 500.0))
    GOOGLE_SHEET_ID = os.getenv("GOOGLE_SHEET_ID", "13YQKq-vRGgc--JEAlgftvEvQxtnhSBXuaqkjbJUoUkI")
    
    # Google APIs scopes (Gmail + Sheets)
    SCOPES = [
        'https://www.googleapis.com/auth/gmail.readonly',
        'https://www.googleapis.com/auth/spreadsheets'
    ]
