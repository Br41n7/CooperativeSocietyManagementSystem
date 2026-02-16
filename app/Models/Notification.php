<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
        'sent_email',
        'sent_sms',
        'email_sent_at',
        'sms_sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'sent_email' => 'boolean',
        'sent_sms' => 'boolean',
        'read_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'sms_sent_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead()
    {
        $this->is_read = true;
        $this->read_at = now();
        $this->save();
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public static function createLoanApproval($userId, $loanNumber, $amount)
    {
        return self::create([
            'user_id' => $userId,
            'type' => 'loan_approval',
            'title' => 'Loan Approved',
            'message' => "Your loan application #{$loanNumber} for ₦" . number_format($amount) . " has been approved.",
            'data' => [
                'loan_number' => $loanNumber,
                'amount' => $amount,
            ],
        ]);
    }

    public static function createLoanRejection($userId, $loanNumber, $reason)
    {
        return self::create([
            'user_id' => $userId,
            'type' => 'loan_rejection',
            'title' => 'Loan Rejected',
            'message' => "Your loan application #{$loanNumber} has been rejected. Reason: {$reason}",
            'data' => [
                'loan_number' => $loanNumber,
                'reason' => $reason,
            ],
        ]);
    }

    public static function createContributionConfirmation($userId, $amount, $transactionNumber)
    {
        return self::create([
            'user_id' => $userId,
            'type' => 'contribution',
            'title' => 'Contribution Received',
            'message' => "Your contribution of ₦" . number_format($amount) . " has been received. Transaction #{$transactionNumber}",
            'data' => [
                'amount' => $amount,
                'transaction_number' => $transactionNumber,
            ],
        ]);
    }

    public static function createRepaymentReminder($userId, $loanNumber, $dueAmount, $dueDate)
    {
        return self::create([
            'user_id' => $userId,
            'type' => 'repayment_reminder',
            'title' => 'Loan Payment Due',
            'message' => "Payment of ₦" . number_format($dueAmount) . " for loan #{$loanNumber} is due on {$dueDate}",
            'data' => [
                'loan_number' => $loanNumber,
                'due_amount' => $dueAmount,
                'due_date' => $dueDate,
            ],
        ]);
    }

    public static function createMeetingNotice($userId, $meetingTitle, $meetingDate)
    {
        return self::create([
            'user_id' => $userId,
            'type' => 'meeting',
            'title' => 'Meeting Notice',
            'message' => "Meeting '{$meetingTitle}' scheduled for {$meetingDate}",
            'data' => [
                'meeting_title' => $meetingTitle,
                'meeting_date' => $meetingDate,
            ],
        ]);
    }
}