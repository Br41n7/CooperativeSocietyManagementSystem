<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'installment_number',
        'due_date',
        'due_amount',
        'principal_amount',
        'interest_amount',
        'paid_amount',
        'payment_date',
        'payment_method',
        'transaction_number',
        'receipt_number',
        'status',
        'days_overdue',
        'penalty_amount',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'payment_date' => 'date',
        'due_amount' => 'decimal:2',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'reference_id')
            ->where('reference_type', LoanRepayment::class);
    }

    public function getRemainingAmountAttribute()
    {
        return $this->due_amount + $this->penalty_amount - $this->paid_amount;
    }

    public function markAsPaid($paymentDate, $paymentMethod, $userId)
    {
        $this->status = 'paid';
        $this->payment_date = $paymentDate;
        $this->paid_amount = $this->due_amount + $this->penalty_amount;
        $this->payment_method = $paymentMethod;
        $this->transaction_number = Transaction::generateTransactionNumber();
        $this->receipt_number = self::generateReceiptNumber();
        $this->save();

        $loan = $this->loan;
        $loan->member->total_loans_repaid += $this->principal_amount;
        $loan->member->save();

        if ($loan->outstanding_balance <= 0) {
            $loan->complete();
        }

        return $this;
    }

    public function checkOverdue()
    {
        if ($this->status !== 'pending') {
            return;
        }

        if (now()->gt($this->due_date)) {
            $this->status = 'overdue';
            $this->days_overdue = now()->diffInDays($this->due_date);
            $this->penalty_amount = $this->calculatePenalty();
            $this->save();

            $loan = $this->loan;
            $overdueCount = $loan->repayments()->where('status', 'overdue')->count();
            
            if ($overdueCount >= 3) {
                $loan->markAsDefaulted();
            }
        }
    }

    private function calculatePenalty()
    {
        $dailyRate = 0.01; 
        return $this->due_amount * $dailyRate * $this->days_overdue;
    }

    public static function generateReceiptNumber()
    {
        $prefix = 'LRP';
        $date = now()->format('Ymd');
        $lastReceipt = self::where('receipt_number', 'like', $prefix . $date . '%')
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastReceipt 
            ? (int) substr($lastReceipt->receipt_number, -4) + 1 
            : 1;

        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeByLoan($query, $loanId)
    {
        return $query->where('loan_id', $loanId);
    }

    public function scopeDueDate($query, $date)
    {
        return $query->whereDate('due_date', $date);
    }
}