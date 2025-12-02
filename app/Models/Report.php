<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'date_from',
        'date_to',
        'filters',
        'file_path',
        'format',
        'status'
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'filters' => 'array'
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'termine');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
