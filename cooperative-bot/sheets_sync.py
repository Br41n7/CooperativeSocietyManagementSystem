import os
from google.oauth2.credentials import Credentials
from googleapiclient.discovery import build
from config import Config
from utils import format_date

class SheetsSync:
    def __init__(self):
        self.service = None
        self.authenticate()

    def authenticate(self):
        """Authenticates with Sheets API using existing token.json"""
        if os.path.exists('token.json'):
            creds = Credentials.from_authorized_user_file('token.json', Config.SCOPES)
            if creds and creds.valid:
                self.service = build('sheets', 'v4', credentials=creds)
            else:
                print("Warning: token.json invalid for Sheets API. Re-authentication required.")
        else:
            print("Warning: token.json not found. Sheets sync disabled.")

    def append_transaction(self, transaction):
        """
        Appends a row to the configured Google Sheet.
        Expects a Google Sheet with Headers: [ID, Date, Payer, Amount, Reference]
        """
        if not self.service or not Config.GOOGLE_SHEET_ID:
            return False
            
        try:
            values = [
                [
                    transaction.get('id', ''),
                    format_date(transaction['time']),
                    transaction['payer'],
                    transaction['amount'],
                    transaction.get('reference', '')
                ]
            ]
            
            body = {'values': values}
            
            # Append to the first sheet
            self.service.spreadsheets().values().append(
                spreadsheetId=Config.GOOGLE_SHEET_ID,
                range='A:E',
                valueInputOption='USER_ENTERED',
                body=body
            ).execute()
            
            return True
        except Exception as e:
            print(f"Error syncing to Google Sheets: {e}")
            return False

sheets_sync = SheetsSync()
