<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'member_number',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'occupation',
        'employer',
        'monthly_income',
        'next_of_kin_name',
        'next_of_kin_phone',
        'next_of_kin_relationship',
        'next_of_kin_address',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'profile_photo',
        'id_document',
        'signature',
        'status',
        'membership_date',
        'last_contribution_date',
        'is_defaulting',
        'credit_score',
        'total_savings',
        'total_loans_taken',
        'total_loans_repaid',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'membership_date' => 'date',
        'last_contribution_date' => 'date',
        'is_defaulting' => 'boolean',
        'monthly_income' => 'decimal:2',
        'total_savings' => 'decimal:2',
        'total_loans_taken' => 'decimal:2',
        'total_loans_repaid' => 'decimal:2',
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function savings()
    {
        return $this->hasMany(Savings::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function activeLoans()
    {
        return $this->hasMany(Loan::class)->where('status', 'active');
    }

    public function pendingLoans()
    {
        return $this->hasMany(Loan::class)->where('status', 'pending');
    }

    public function completedLoans()
    {
        return $this->hasMany(Loan::class)->where('status', 'completed');
    }

    public function loanRepayments()
    {
        return $this->hasManyThrough(LoanRepayment::class, Loan::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function meetingAttendance()
    {
        return $this->hasMany(MeetingAttendance::class);
    }

    public function voteResponses()
    {
        return $this->hasMany(VoteResponse::class);
    }

    public function guaranteedLoans()
    {
        return $this->hasMany(Loan::class, 'guarantor_member_id');
    }

    public function getSavingsBalanceAttribute()
    {
        return $this->savings()->sum('amount');
    }

    public function getOutstandingLoanBalanceAttribute()
    {
        return $this->loans()
            ->where('status', 'active')
            ->sum(function ($loan) {
                return $loan->total_repayment - $loan->total_repaid;
            });
    }

    public function getLoanEligibilityAttribute()
    {
        $savingsRatio = $this->savings_balance * 3;
        $incomeRatio = $this->monthly_income * 2;
        return min($savingsRatio, $incomeRatio, 500000);
    }

    public function getDefaultRiskAttribute()
    {
        if ($this->is_defaulting) {
            return 'high';
        }

        $overduePayments = $this->loanRepayments()
            ->where('status', 'overdue')
            ->count();

        if ($overduePayments > 3) {
            return 'high';
        } elseif ($overduePayments > 0) {
            return 'medium';
        }

        if ($this->credit_score < 70) {
            return 'medium';
        }

        return 'low';
    }

    public function updateCreditScore()
    {
        $baseScore = 100;
        $penalties = 0;

        if ($this->is_defaulting) {
            $penalties += 30;
        }

        $overduePayments = $this->loanRepayments()
            ->where('status', 'overdue')
            ->count();

        $penalties += min($overduePayments * 5, 30);

        $repaymentRatio = $this->total_loans_taken > 0 
            ? ($this->total_loans_repaid / $this->total_loans_taken) * 100 
            : 100;

        if ($repaymentRatio >= 95) {
            $baseScore += 10;
        } elseif ($repaymentRatio < 80) {
            $penalties += 15;
        }

        $this->credit_score = max($baseScore - $penalties, 0);
        $this->save();
    }

    public static function generateMemberNumber()
    {
        $prefix = 'MEM';
        $date = now()->format('Ym');
        $lastMember = self::orderBy('id', 'desc')->first();

        $sequence = $lastMember 
            ? (int) substr($lastMember->member_number, -6) + 1 
            : 1;

        return sprintf('%s%s%06d', $prefix, $date, $sequence);
    }
}