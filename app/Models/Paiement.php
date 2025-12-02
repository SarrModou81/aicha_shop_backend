<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    use HasFactory;

    protected $fillable = [
        'commande_id',
        'payment_method',
        'status',
        'amount',
        'transaction_id',
        'payment_details'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array'
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function isPaid()
    {
        return $this->status === 'paye';
    }
}
