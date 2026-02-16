<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoteResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'vote_id',
        'member_id',
        'response',
        'voted_at',
    ];

    protected $casts = [
        'voted_at' => 'datetime',
    ];

    public function vote()
    {
        return $this->belongsTo(Vote::class);
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}