<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'question',
        'vote_type',
        'options',
        'start_time',
        'end_time',
        'status',
        'total_votes',
        'created_by',
    ];

    protected $casts = [
        'options' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responses()
    {
        return $this->hasMany(VoteResponse::class);
    }

    public function getResultsAttribute()
    {
        $results = [];
        
        if ($this->vote_type === 'yes_no') {
            $results['yes'] = $this->responses()->where('response', 'yes')->count();
            $results['no'] = $this->responses()->where('response', 'no')->count();
        } elseif ($this->vote_type === 'multiple_choice') {
            foreach ($this->options as $option) {
                $results[$option] = $this->responses()->where('response', $option)->count();
            }
        }

        return $results;
    }

    public function getWinnerAttribute()
    {
        $results = $this->results;
        return array_key_first($results) ? 
               array_search(max($results), $results) : 
               null;
    }

    public function hasVoted($memberId)
    {
        return $this->responses()->where('member_id', $memberId)->exists();
    }

    public function castVote($memberId, $response)
    {
        if ($this->hasVoted($memberId)) {
            return false;
        }

        return $this->responses()->create([
            'member_id' => $memberId,
            'response' => $response,
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now());
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
}