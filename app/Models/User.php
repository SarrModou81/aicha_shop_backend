<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;  // Ajoutez cette ligne


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;  // Ajoutez HasApiTokens ici

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'city',
        'country',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed'
    ];

    // Relations
    public function produits()
    {
        return $this->hasMany(Produit::class, 'vendeur_id');
    }

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }

    public function panier()
    {
        return $this->hasMany(Panier::class);
    }

    public function avis()
    {
        return $this->hasMany(Avis::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Scopes
    public function scopeClients($query)
    {
        return $query->where('role', 'client');
    }

    public function scopeVendeurs($query)
    {
        return $query->where('role', 'vendeur');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
