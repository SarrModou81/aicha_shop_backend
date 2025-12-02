<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'shipping_cost',
        'estimated_days',
        'cities',
        'is_active'
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
        'estimated_days' => 'integer',
        'cities' => 'array',
        'is_active' => 'boolean'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Méthode pour trouver la zone de livraison par ville
    public static function findByCity($city)
    {
        return self::active()
            ->where(function($query) use ($city) {
                $query->where('name', 'like', "%{$city}%")
                      ->orWhereJsonContains('cities', $city);
            })
            ->first();
    }
}
