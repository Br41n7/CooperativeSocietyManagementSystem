<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Savings extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'transaction_number',
        'amount',
        'contribution_type',
        'payment_method',
        'payment_date',
        'month',
        'year',
        'receipt_number',
        'receipt_generated',
        'notes',
        'is_adjusted',
        'original_savings_id',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'receipt_generated' => 'boolean',
        'is_adjusted' => 'boolean',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function originalSavings()
    {
        return $this->belongsTo(Savings::class, 'original_savings_id');
    }

    public function adjustedSavings()
    {
        return $this->hasMany(Savings::class, 'original_savings_id');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'reference_id')->where('reference_type', Savings::class);
    }

    public static function generateTransactionNumber()
    {
        $prefix = 'SAV';
        $date = now()->format('Ymd');
        $lastSavings = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastSavings 
            ? (int) substr($lastSavings->transaction_number, -4) + 1 
            : 1;

        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }

    public static function generateReceiptNumber()
    {
        $prefix = 'RCP';
        $date = now()->format('Ymd');
        $lastReceipt = self::where('receipt_generated', true)
            ->whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastReceipt 
            ? (int) substr($lastReceipt->receipt_number, -4) + 1 
            : 1;

        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }

    public function scopeByMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('contribution_type', $type);
    }

    public function scopeByMonth($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }
}