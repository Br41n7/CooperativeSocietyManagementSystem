import os
from datetime import datetime
from reportlab.lib.pagesizes import letter
from reportlab.lib import colors
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from utils import format_currency, format_date

def generate_receipt(transaction, filename="receipt.pdf"):
    """
    Generates a PDF receipt for a given transaction.
    transaction should be a dict with:
    - id, amount, payer, time, reference
    """
    doc = SimpleDocTemplate(filename, pagesize=letter, rightMargin=72, leftMargin=72, topMargin=72, bottomMargin=18)
    
    styles = getSampleStyleSheet()
    styles.add(ParagraphStyle(name='CenterTitle', alignment=1, fontSize=24, spaceAfter=20, fontName="Helvetica-Bold"))
    styles.add(ParagraphStyle(name='CenterSub', alignment=1, fontSize=14, spaceAfter=30, textColor=colors.dimgrey))
    
    Story = []
    
    # Title
    Story.append(Paragraph("Cooperative Society", styles['CenterTitle']))
    Story.append(Paragraph("Payment Receipt", styles['CenterSub']))
    Story.append(Spacer(1, 12))
    
    # Details Table
    data = [
        ["Receipt No:", f"#{transaction.get('id', 'N/A')}"],
        ["Date & Time:", format_date(transaction['time'])],
        ["Received From:", transaction['payer']],
        ["Amount:", format_currency(transaction['amount'])],
        ["Reference / UTR:", transaction.get('reference', 'N/A')]
    ]
    
    t = Table(data, colWidths=[150, 250])
    t.setStyle(TableStyle([
        ('TEXTCOLOR', (0, 0), (0, -1), colors.darkblue),
        ('ALIGN', (0, 0), (-1, -1), 'LEFT'),
        ('FONTNAME', (0, 0), (0, -1), 'Helvetica-Bold'),
        ('FONTNAME', (1, 0), (1, -1), 'Helvetica'),
        ('FONTSIZE', (0, 0), (-1, -1), 12),
        ('BOTTOMPADDING', (0, 0), (-1, -1), 12),
        ('LINEBELOW', (0, 0), (-1, -1), 1, colors.lightgrey),
    ]))
    
    Story.append(t)
    Story.append(Spacer(1, 40))
    
    # Footer
    footer_style = ParagraphStyle(name='Footer', alignment=1, fontSize=10, textColor=colors.grey)
    Story.append(Paragraph("Thank you for your contribution!", footer_style))
    Story.append(Paragraph("This is an automatically generated receipt.", footer_style))
    
    doc.build(Story)
    return filename
