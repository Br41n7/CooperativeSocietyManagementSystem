<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'document_type',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'member_id',
        'uploaded_by',
        'meeting_id',
        'loan_id',
        'is_public',
        'download_count',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function incrementDownload()
    {
        $this->increment('download_count');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    public function scopeByMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    public function getFilePathAttribute()
    {
        return storage_path('app/' . $this->file_path);
    }

    public function getDownloadUrlAttribute()
    {
        return route('documents.download', $this->id);
    }
}