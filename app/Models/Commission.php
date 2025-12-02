<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendeur_id',
        'commande_id',
        'order_amount',
        'commission_rate',
        'commission_amount',
        'vendeur_amount',
        'status',
        'paid_at'
    ];

    protected $casts = [
        'order_amount' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'vendeur_amount' => 'decimal:2',
        'paid_at' => 'datetime'
    ];

    // Relations
    public function vendeur()
    {
        return $this->belongsTo(User::class, 'vendeur_id');
    }

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    // Scopes
    public function scopeUnpaid($query)
    {
        return $query->where('status', 'en_attente');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paye');
    }

    public function scopeByVendeur($query, $vendeurId)
    {
        return $query->where('vendeur_id', $vendeurId);
    }

    // Méthode pour calculer la commission
    public static function calculateCommission($vendeurId, $commandeId, $orderAmount)
    {
        // Récupérer le taux de commission du système ou personnalisé pour le vendeur
        $defaultRate = SystemSetting::get('commission_rate', 10); // 10% par défaut

        // TODO: Vérifier si le vendeur a un taux personnalisé
        $commissionRate = $defaultRate;

        $commissionAmount = ($orderAmount * $commissionRate) / 100;
        $vendeurAmount = $orderAmount - $commissionAmount;

        return self::create([
            'vendeur_id' => $vendeurId,
            'commande_id' => $commandeId,
            'order_amount' => $orderAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'vendeur_amount' => $vendeurAmount,
            'status' => 'en_attente'
        ]);
    }
}
