import matplotlib.pyplot as plt
import matplotlib.ticker as ticker
import calendar
import os

def generate_monthly_chart(summary_data, year, filename="chart.png"):
    """
    summary_data is a dict from database.get_monthly_summary(year)
    Format: { month_int: {'amount': float, ...} }
    """
    months = list(range(1, 13))
    month_names = [calendar.month_abbr[m] for m in months]
    
    amounts = []
    for m in months:
        if m in summary_data:
            amounts.append(summary_data[m]['amount'])
        else:
            amounts.append(0.0)
            
    fig, ax = plt.subplots(figsize=(10, 6))
    
    bars = ax.bar(month_names, amounts, color='#4A90E2', edgecolor='black', alpha=0.8)
    
    ax.set_title(f'Collection Trends - {year}', fontsize=16, fontweight='bold', pad=20)
    ax.set_xlabel('Month', fontsize=12)
    ax.set_ylabel('Amount (₹)', fontsize=12)
    
    # Format Y axis as currency
    formatter = ticker.FuncFormatter(lambda x, pos: f'₹{x:,.0f}')
    ax.yaxis.set_major_formatter(formatter)
    
    # Add value labels on top of bars
    for bar in bars:
        height = bar.get_height()
        if height > 0:
            ax.annotate(f'₹{height/1000:,.1f}k' if height >= 1000 else f'₹{height:,.0f}',
                        xy=(bar.get_x() + bar.get_width() / 2, height),
                        xytext=(0, 3),  # 3 points vertical offset
                        textcoords="offset points",
                        ha='center', va='bottom', fontsize=9, rotation=45)
                        
    ax.spines['top'].set_visible(False)
    ax.spines['right'].set_visible(False)
    ax.grid(axis='y', linestyle='--', alpha=0.7)
    
    plt.tight_layout()
    plt.savefig(filename, dpi=300, bbox_inches='tight')
    plt.close()
    
    return filename
