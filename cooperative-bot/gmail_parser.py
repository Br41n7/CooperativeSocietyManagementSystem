import os
import base64
from bs4 import BeautifulSoup
from google.oauth2.credentials import Credentials
from google_auth_oauthlib.flow import InstalledAppFlow
from google.auth.transport.requests import Request
from googleapiclient.discovery import build
from datetime import datetime, timedelta
import pytz

from config import Config
from utils import extract_transaction_details
from database import db

class GmailParser:
    def __init__(self):
        self.creds = None
        self.service = None
        self.authenticate()

    def authenticate(self):
        """Authenticates with Gmail API."""
        if os.path.exists('token.json'):
            self.creds = Credentials.from_authorized_user_file('token.json', Config.SCOPES)
            
        if not self.creds or not self.creds.valid:
            if self.creds and self.creds.expired and self.creds.refresh_token:
                self.creds.refresh(Request())
            else:
                if os.path.exists('credentials.json'):
                    flow = InstalledAppFlow.from_client_secrets_file(
                        'credentials.json', Config.SCOPES)
                    # Use a local server for manual auth
                    self.creds = flow.run_local_server(port=0)
                else:
                    print("Warning: credentials.json not found. Gmail integration will not work.")
                    return
            
            # Save the credentials for the next run
            with open('token.json', 'w') as token:
                token.write(self.creds.to_json())
                
        self.service = build('gmail', 'v1', credentials=self.creds)

    def get_email_content(self, msg_id):
        """Fetches and decodes the email content."""
        if not self.service:
            return None
            
        try:
            message = self.service.users().messages().get(userId='me', id=msg_id, format='full').execute()
            
            # Extract headers
            headers = message['payload'].get('headers', [])
            subject = next((h['value'] for h in headers if h['name'].lower() == 'subject'), '')
            sender = next((h['value'] for h in headers if h['name'].lower() == 'from'), '')
            date_str = next((h['value'] for h in headers if h['name'].lower() == 'date'), '')
            
            # Parse Date
            # E.g. "Thu, 15 Feb 2024 10:30:00 +0530"
            try:
                # Basic parsing, might need more robust dateutils in prod
                email_time = datetime.strptime(date_str[:25].strip(), "%a, %d %b %Y %H:%M:%S")
            except ValueError:
                email_time = datetime.now()
            
            # Extract Body
            body = ""
            if 'parts' in message['payload']:
                for part in message['payload']['parts']:
                    if part['mimeType'] == 'text/plain':
                        data = part['body'].get('data')
                        if data:
                            body += base64.urlsafe_b64decode(data).decode('utf-8')
                    elif part['mimeType'] == 'text/html':
                        data = part['body'].get('data')
                        if data:
                            html_content = base64.urlsafe_b64decode(data).decode('utf-8')
                            soup = BeautifulSoup(html_content, 'html.parser')
                            body += soup.get_text(separator=' ')
            else:
                data = message['payload']['body'].get('data')
                if data:
                    body = base64.urlsafe_b64decode(data).decode('utf-8')
                    
            return {
                'subject': subject,
                'from': sender,
                'date': email_time,
                'body': body,
                'raw': message
            }
        except Exception as e:
            print(f"Error fetching email {msg_id}: {e}")
            return None

    def fetch_new_payments(self, minutes_ago=15):
        """Searches for recent payment emails and processes them."""
        if not self.service:
            return []
            
        # Calculate time threshold
        tz = pytz.timezone(Config.TIMEZONE)
        threshold_time = datetime.now(tz) - timedelta(minutes=minutes_ago)
        timestamp = int(threshold_time.timestamp())
        
        # Build query
        keywords = ['payment', 'received', 'transfer', 'deposit', 'credited', 'UPI']
        query = f"after:{timestamp} (" + " OR ".join(keywords) + ")"
        
        new_transactions = []
        try:
            results = self.service.users().messages().list(userId='me', q=query).execute()
            messages = results.get('messages', [])
            
            for msg in messages:
                email_data = self.get_email_content(msg['id'])
                if email_data:
                    details = extract_transaction_details(email_data['subject'], email_data['body'])
                    
                    # Only add if we found an amount
                    if details['amount'] > 0:
                        tx_dict = {
                            'amount': details['amount'],
                            'payer': details['payer'],
                            'time': email_data['date'],
                            'reference': details['reference'],
                            'email_subject': email_data['subject'],
                            'email_from': email_data['from'],
                            'raw_content': email_data['body'][:1000] # Store up to 1000 chars of body
                        }
                        
                        # Save to database
                        tx_id = db.add_transaction(tx_dict)
                        if tx_id:
                            tx_dict['id'] = tx_id
                            new_transactions.append(tx_dict)
                            
            return new_transactions
        except Exception as e:
            print(f"Error searching emails: {e}")
            return []

gmail_parser = GmailParser()
