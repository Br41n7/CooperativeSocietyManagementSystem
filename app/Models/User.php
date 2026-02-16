<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'member_id',
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission($permission)
    {
        return $this->role && $this->role->permissions && 
               in_array($permission, json_decode($this->role->permissions, true) ?? []);
    }

    public function hasRole($roleName)
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function uploadedDocuments()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function createdMeetings()
    {
        return $this->hasMany(Meeting::class, 'created_by');
    }

    public function createdVotes()
    {
        return $this->hasMany(Vote::class, 'created_by');
    }

    public function createdSavings()
    {
        return $this->hasMany(Savings::class, 'created_by');
    }

    public function createdTransactions()
    {
        return $this->hasMany(Transaction::class, 'created_by');
    }

    public function reversedTransactions()
    {
        return $this->hasMany(Transaction::class, 'reversed_by');
    }

    public function approvedLoansAsSecretary()
    {
        return $this->hasMany(Loan::class, 'secretary_approved_by');
    }

    public function approvedLoansAsChairman()
    {
        return $this->hasMany(Loan::class, 'chairman_approved_by');
    }

    public function approvedLoansAsTreasurer()
    {
        return $this->hasMany(Loan::class, 'treasurer_approved_by');
    }
}