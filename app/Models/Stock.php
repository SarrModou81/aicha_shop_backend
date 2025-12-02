<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'produit_id',
        'quantity',
        'low_stock_threshold'
    ];

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    public function isLowStock()
    {
        return $this->quantity <= $this->low_stock_threshold;
    }
}
