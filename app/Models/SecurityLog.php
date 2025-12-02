<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'ip_address',
        'user_agent',
        'description',
        'level',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Méthodes statiques pour logger
    public static function logAction($userId, $action, $description, $level = 'info', $metadata = [])
    {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => $description,
            'level' => $level,
            'metadata' => $metadata
        ]);
    }
}
