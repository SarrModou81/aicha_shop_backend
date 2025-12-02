<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Livraison extends Model
{
    use HasFactory;

    protected $fillable = [
        'commande_id',
        'address',
        'city',
        'phone',
        'tracking_number',
        'status',
        'estimated_delivery',
        'delivered_at'
    ];

    protected $casts = [
        'estimated_delivery' => 'date',
        'delivered_at' => 'date'
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function markAsDelivered()
    {
        $this->status = 'livree';
        $this->delivered_at = now();
        $this->save();
    }
}
