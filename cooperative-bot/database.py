import os
import pandas as pd
from datetime import datetime
from sqlalchemy import create_engine, Column, Integer, String, Float, DateTime, Text
from sqlalchemy.orm import declarative_base, sessionmaker
from sqlalchemy.sql import extract, func
from config import Config

Base = declarative_base()

class Transaction(Base):
    __tablename__ = 'transactions'

    id = Column(Integer, primary_key=True, autoincrement=True)
    amount = Column(Float, nullable=False)
    payer = Column(String(255), nullable=False)
    time = Column(DateTime, nullable=False)
    reference = Column(String(255), nullable=True)
    email_subject = Column(String(255), nullable=True)
    email_from = Column(String(255), nullable=True)
    raw_content = Column(Text, nullable=True)

class Expense(Base):
    __tablename__ = 'expenses'
    
    id = Column(Integer, primary_key=True, autoincrement=True)
    amount = Column(Float, nullable=False)
    description = Column(String(255), nullable=False)
    time = Column(DateTime, nullable=False)

class Database:
    def __init__(self, db_url=Config.DATABASE_URL):
        # Allow multi-threading for SQLite
        connect_args = {'check_same_thread': False} if db_url.startswith('sqlite') else {}
        self.engine = create_engine(db_url, connect_args=connect_args)
        Base.metadata.create_all(self.engine)
        self.Session = sessionmaker(bind=self.engine)

    def add_transaction(self, transaction_dict):
        session = self.Session()
        try:
            new_tx = Transaction(**transaction_dict)
            session.add(new_tx)
            session.commit()
            return new_tx.id
        except Exception as e:
            session.rollback()
            print(f"Error adding transaction: {e}")
            return None
        finally:
            session.close()

    def get_recent(self, limit=10):
        session = self.Session()
        try:
            return session.query(Transaction).order_by(Transaction.time.desc()).limit(limit).all()
        finally:
            session.close()

    def get_total_amount(self):
        session = self.Session()
        try:
            total = session.query(func.sum(Transaction.amount)).scalar()
            return total or 0.0
        finally:
            session.close()

    def get_transaction_count(self):
        session = self.Session()
        try:
            return session.query(func.count(Transaction.id)).scalar()
        finally:
            session.close()

    def get_transactions_after(self, date):
        session = self.Session()
        try:
            return session.query(Transaction).filter(Transaction.time >= date).order_by(Transaction.time.desc()).all()
        finally:
            session.close()

    def get_transactions_by_month(self, year, month):
        session = self.Session()
        try:
            start_date = datetime(year, month, 1)
            if month == 12:
                end_date = datetime(year + 1, 1, 1)
            else:
                end_date = datetime(year, month + 1, 1)
            
            return session.query(Transaction).filter(
                Transaction.time >= start_date,
                Transaction.time < end_date
            ).order_by(Transaction.time.desc()).all()
        finally:
            session.close()

    def get_monthly_summary(self, year):
        session = self.Session()
        try:
            start_date = datetime(year, 1, 1)
            end_date = datetime(year + 1, 1, 1)
            
            transactions = session.query(Transaction).filter(
                Transaction.time >= start_date,
                Transaction.time < end_date
            ).all()
            
            summary = {}
            for t in transactions:
                month = t.time.month
                if month not in summary:
                    summary[month] = {'amount': 0.0, 'count': 0, 'payers': set()}
                summary[month]['amount'] += t.amount
                summary[month]['count'] += 1
                summary[month]['payers'].add(t.payer.lower())
                
            return summary
        finally:
            session.close()

    def search_by_payer(self, name):
        session = self.Session()
        try:
            return session.query(Transaction).filter(
                Transaction.payer.ilike(f'%{name}%')
            ).order_by(Transaction.time.desc()).all()
        finally:
            session.close()

    def search_by_date_range(self, start_date, end_date):
        session = self.Session()
        try:
            return session.query(Transaction).filter(
                Transaction.time >= start_date,
                Transaction.time <= end_date
            ).order_by(Transaction.time.desc()).all()
        finally:
            session.close()

    def get_unique_payers(self):
        session = self.Session()
        try:
            payers = session.query(Transaction.payer).distinct().all()
            return [p[0] for p in payers]
        finally:
            session.close()

    def get_payer_total(self, name):
        session = self.Session()
        try:
            total = session.query(func.sum(Transaction.amount)).filter(
                Transaction.payer.ilike(f'%{name}%')
            ).scalar()
            return total or 0.0
        finally:
            session.close()

    def update_transaction(self, id, updates):
        session = self.Session()
        try:
            session.query(Transaction).filter(Transaction.id == id).update(updates)
            session.commit()
            return True
        except Exception as e:
            session.rollback()
            print(f"Error updating transaction: {e}")
            return False
        finally:
            session.close()

    def delete_transaction(self, id):
        session = self.Session()
        try:
            session.query(Transaction).filter(Transaction.id == id).delete()
            session.commit()
            return True
        except Exception as e:
            session.rollback()
            print(f"Error deleting transaction: {e}")
            return False
        finally:
            session.close()

    def export_to_csv(self, filename="transactions.csv", transactions=None):
        session = self.Session()
        try:
            if transactions is None:
                transactions = session.query(Transaction).all()
            
            data = []
            for t in transactions:
                data.append({
                    'ID': t.id,
                    'Date': t.time.strftime('%Y-%m-%d %H:%M:%S'),
                    'Amount': t.amount,
                    'Payer': t.payer,
                    'Reference': t.reference,
                    'Email Subject': t.email_subject
                })
                
            df = pd.DataFrame(data)
            df.to_csv(filename, index=False)
            return filename
        finally:
            session.close()

    def export_monthly_report(self, year, month):
        month_name = datetime(year, month, 1).strftime('%B').lower()
        filename = f"cooperative_{month_name}_{year}.csv"
        transactions = self.get_transactions_by_month(year, month)
        return self.export_to_csv(filename, transactions)

    # --- Expense Tracking ---
    
    def add_expense(self, amount, description, time=None):
        session = self.Session()
        try:
            new_expense = Expense(
                amount=amount, 
                description=description, 
                time=time or datetime.now()
            )
            session.add(new_expense)
            session.commit()
            return new_expense.id
        except Exception as e:
            session.rollback()
            print(f"Error adding expense: {e}")
            return None
        finally:
            session.close()

    def get_total_expenses(self):
        session = self.Session()
        try:
            total = session.query(func.sum(Expense.amount)).scalar()
            return total or 0.0
        finally:
            session.close()
            
    def get_monthly_expenses(self, year):
        session = self.Session()
        try:
            start_date = datetime(year, 1, 1)
            end_date = datetime(year + 1, 1, 1)
            
            expenses = session.query(Expense).filter(
                Expense.time >= start_date,
                Expense.time < end_date
            ).all()
            
            summary = {}
            for e in expenses:
                month = e.time.month
                if month not in summary:
                    summary[month] = 0.0
                summary[month] += e.amount
                
            return summary
        finally:
            session.close()

    def get_pending_payers(self, year, month, required_amount):
        """Returns a list of all known payers who have paid less than the required amount this month."""
        all_payers = self.get_unique_payers()
        
        session = self.Session()
        try:
            start_date = datetime(year, month, 1)
            if month == 12:
                end_date = datetime(year + 1, 1, 1)
            else:
                end_date = datetime(year, month + 1, 1)
                
            # Get totals for this month grouped by payer
            results = session.query(
                Transaction.payer, func.sum(Transaction.amount)
            ).filter(
                Transaction.time >= start_date,
                Transaction.time < end_date
            ).group_by(Transaction.payer).all()
            
            paid_amounts = {payer.lower(): amount for payer, amount in results}
            
            pending = []
            for payer in all_payers:
                total_paid = paid_amounts.get(payer.lower(), 0.0)
                if total_paid < required_amount:
                    pending.append({
                        'payer': payer,
                        'paid': total_paid,
                        'due': required_amount - total_paid
                    })
                    
            return pending
        finally:
            session.close()

# Global DB instance
db = Database()
