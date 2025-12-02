<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'compare_price',
        'category_id',
        'marque_id',
        'vendeur_id',
        'is_featured',
        'is_active',
        'status',
        'images'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'images' => 'array'
    ];

    // Relations
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function marque()
    {
        return $this->belongsTo(Marque::class);
    }

    public function vendeur()
    {
        return $this->belongsTo(User::class, 'vendeur_id');
    }

    public function stock()
    {
        return $this->hasOne(Stock::class);
    }

    public function avis()
    {
        return $this->hasMany(Avis::class);
    }

    public function paniers()
    {
        return $this->hasMany(Panier::class);
    }

    public function detailCommandes()
    {
        return $this->hasMany(DetailCommande::class);
    }

    // Accessors
    public function getFeaturedImageAttribute()
    {
        $images = $this->images ?? [];
        return !empty($images) ? $images[0] : null;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'approuve');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByVendeur($query, $vendeurId)
    {
        return $query->where('vendeur_id', $vendeurId);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%");
    }
}
