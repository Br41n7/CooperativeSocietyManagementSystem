<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'meeting_type',
        'meeting_date',
        'venue',
        'agenda',
        'minutes',
        'status',
        'notify_members',
        'total_attendees',
        'created_by',
    ];

    protected $casts = [
        'meeting_date' => 'datetime',
        'notify_members' => 'boolean',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendance()
    {
        return $this->hasMany(MeetingAttendance::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function getPresentMembersAttribute()
    {
        return $this->attendance()->where('status', 'present')->count();
    }

    public function getAbsentMembersAttribute()
    {
        return $this->attendance()->where('status', 'absent')->count();
    }

    public function getExcusedMembersAttribute()
    {
        return $this->attendance()->where('status', 'excused')->count();
    }

    public function addAttendee($memberId, $status = 'absent')
    {
        return $this->attendance()->updateOrCreate(
            ['member_id' => $memberId],
            ['status' => $status]
        );
    }

    public function markAttendance($memberId, $status, $checkInTime = null)
    {
        $attendance = $this->attendance()->where('member_id', $memberId)->first();
        
        if ($attendance) {
            $attendance->update([
                'status' => $status,
                'check_in_time' => $checkInTime ?? now(),
            ]);
        }

        $this->total_attendees = $this->present_members;
        $this->save();

        return $attendance;
    }

    public function scopeUpcoming($query)
    {
        return $query->where('meeting_date', '>', now());
    }

    public function scopePast($query)
    {
        return $query->where('meeting_date', '<', now());
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}