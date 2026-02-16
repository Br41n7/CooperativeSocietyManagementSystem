<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'transaction_type',
        'reference_id',
        'reference_type',
        'member_id',
        'amount',
        'description',
        'transaction_date',
        'created_by',
        'is_reversed',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'datetime',
        'is_reversed' => 'boolean',
        'reversed_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public static function generateTransactionNumber()
    {
        $prefix = 'TXN';
        $date = now()->format('Ymd');
        $lastTransaction = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastTransaction 
            ? (int) substr($lastTransaction->transaction_number, -4) + 1 
            : 1;

        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }

    public function reverse($reason, $userId)
    {
        if ($this->is_reversed) {
            return false;
        }

        $this->is_reversed = true;
        $this->reversed_at = now();
        $this->reversed_by = $userId;
        $this->reversal_reason = $reason;
        $this->save();

        if ($this->transaction_type === 'savings') {
            $member = $this->member;
            $member->total_savings -= $this->amount;
            $member->save();
        }

        return true;
    }

    public function scopeByMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeReversed($query)
    {
        return $query->where('is_reversed', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_reversed', false);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}