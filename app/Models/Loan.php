<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'loan_number',
        'amount',
        'interest_rate',
        'interest_type',
        'total_interest',
        'total_repayment',
        'purpose',
        'repayment_period',
        'repayment_frequency',
        'start_date',
        'end_date',
        'monthly_repayment',
        'collateral',
        'guarantor_name',
        'guarantor_phone',
        'guarantor_address',
        'guarantor_member_id',
        'status',
        'disbursement_date',
        'disbursement_method',
        'disbursement_reference',
        'secretary_approved_at',
        'secretary_approved_by',
        'chairman_approved_at',
        'chairman_approved_by',
        'treasurer_approved_at',
        'treasurer_approved_by',
        'rejection_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'total_interest' => 'decimal:2',
        'total_repayment' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_repayment' => 'decimal:2',
        'disbursement_date' => 'date',
        'secretary_approved_at' => 'datetime',
        'chairman_approved_at' => 'datetime',
        'treasurer_approved_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function guarantor()
    {
        return $this->belongsTo(Member::class, 'guarantor_member_id');
    }

    public function repayments()
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function document()
    {
        return $this->hasOne(Document::class, 'loan_id');
    }

    public function secretaryApproval()
    {
        return $this->belongsTo(User::class, 'secretary_approved_by');
    }

    public function chairmanApproval()
    {
        return $this->belongsTo(User::class, 'chairman_approved_by');
    }

    public function treasurerApproval()
    {
        return $this->belongsTo(User::class, 'treasurer_approved_by');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'reference_id')
            ->where('reference_type', Loan::class)
            ->where('transaction_type', 'loan_disbursement');
    }

    public function getTotalRepaidAttribute()
    {
        return $this->repayments()->sum('paid_amount');
    }

    public function getOutstandingBalanceAttribute()
    {
        return $this->total_repayment - $this->total_repaid;
    }

    public function getOverdueAmountAttribute()
    {
        return $this->repayments()
            ->where('status', 'overdue')
            ->sum('due_amount');
    }

    public function getNextPaymentAttribute()
    {
        return $this->repayments()
            ->where('status', 'pending')
            ->orderBy('due_date')
            ->first();
    }

    public function getCompletionPercentageAttribute()
    {
        if ($this->total_repayment == 0) {
            return 0;
        }
        return ($this->total_repaid / $this->total_repayment) * 100;
    }

    public function isOverdue()
    {
        return $this->repayments()
            ->where('status', 'overdue')
            ->exists();
    }

    public function canBeApproved($role)
    {
        switch ($role) {
            case 'secretary':
                return $this->status === 'pending';
            case 'chairman':
                return $this->status === 'secretary_approved';
            case 'treasurer':
                return $this->status === 'chairman_approved';
            default:
                return false;
        }
    }

    public function approve($role, $userId)
    {
        switch ($role) {
            case 'secretary':
                $this->status = 'secretary_approved';
                $this->secretary_approved_at = now();
                $this->secretary_approved_by = $userId;
                break;
            case 'chairman':
                $this->status = 'chairman_approved';
                $this->chairman_approved_at = now();
                $this->chairman_approved_by = $userId;
                break;
            case 'treasurer':
                $this->status = 'approved';
                $this->treasurer_approved_at = now();
                $this->treasurer_approved_by = $userId;
                break;
        }
        $this->save();
    }

    public function reject($reason, $userId)
    {
        $this->status = 'rejected';
        $this->rejection_reason = $reason;
        $this->save();

        $this->repayments()->delete();
    }

    public function disburse($date, $method, $reference)
    {
        $this->status = 'active';
        $this->disbursement_date = $date;
        $this->disbursement_method = $method;
        $this->disbursement_reference = $reference;
        $this->save();

        $this->member->total_loans_taken += $this->amount;
        $this->member->save();
    }

    public function complete()
    {
        $this->status = 'completed';
        $this->save();

        $this->member->total_loans_repaid += $this->total_repayment;
        $this->member->updateCreditScore();
    }

    public function markAsDefaulted()
    {
        $this->status = 'defaulted';
        $this->member->is_defaulting = true;
        $this->member->save();
        $this->save();
    }

    public static function generateLoanNumber()
    {
        $prefix = 'LN';
        $date = now()->format('Ymd');
        $lastLoan = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastLoan 
            ? (int) substr($lastLoan->loan_number, -4) + 1 
            : 1;

        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }

    public function calculateInterest()
    {
        switch ($this->interest_type) {
            case 'flat':
                return $this->amount * ($this->interest_rate / 100) * ($this->repayment_period / 12);
            case 'reducing':
                $rate = $this->interest_rate / 100 / 12;
                $n = $this->repayment_period;
                $p = $this->amount;
                $emi = $p * $rate * pow(1 + $rate, $n) / (pow(1 + $rate, $n) - 1);
                return ($emi * $n) - $p;
            case 'compound':
                return $this->amount * pow(1 + ($this->interest_rate / 100 / 12), $this->repayment_period) - $this->amount;
            default:
                return 0;
        }
    }

    public function generateRepaymentSchedule()
    {
        $this->repayments()->delete();

        $startDate = $this->start_date;
        $interval = $this->getRepaymentInterval();

        $totalPrincipal = $this->amount;
        $totalInterest = $this->total_interest;
        $totalPayment = $this->total_repayment;

        $installmentPrincipal = $totalPrincipal / $this->repayment_period;
        $installmentInterest = $totalInterest / $this->repayment_period;

        for ($i = 1; $i <= $this->repayment_period; $i++) {
            $dueDate = $startDate->copy()->addMonths($i - 1);

            if ($this->interest_type === 'reducing') {
                $rate = $this->interest_rate / 100 / 12;
                $outstandingPrincipal = $totalPrincipal - ($installmentPrincipal * ($i - 1));
                $installmentInterest = $outstandingPrincipal * $rate;
                $installmentPrincipal = $this->monthly_repayment - $installmentInterest;
            }

            LoanRepayment::create([
                'loan_id' => $this->id,
                'installment_number' => $i,
                'due_date' => $dueDate,
                'due_amount' => $installmentPrincipal + $installmentInterest,
                'principal_amount' => $installmentPrincipal,
                'interest_amount' => $installmentInterest,
                'status' => 'pending',
            ]);
        }
    }

    private function getRepaymentInterval()
    {
        return match ($this->repayment_frequency) {
            'weekly' => '1 week',
            'bi-weekly' => '2 weeks',
            'monthly' => '1 month',
            default => '1 month',
        };
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    public function scopeOverdue($query)
    {
        return $query->whereHas('repayments', function ($q) {
            $q->where('status', 'overdue');
        });
    }
}