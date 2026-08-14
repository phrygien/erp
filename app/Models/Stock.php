<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    protected $fillable = [
        'product_id',
        'quantite',
        'gen_code',
    ];

    protected $casts = [
        'quantite' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Lots FIFO de ce produit (un lot = une ligne de réception non encore
     * épuisée ou épuisée). Utilisé pour calculer/consommer le stock dans
     * l'ordre d'entrée (date_entree croissante).
     */
    public function lots(): HasMany
    {
        return $this->hasMany(StockLot::class, 'product_id', 'product_id');
    }

    /**
     * Historique complet des mouvements (entrées, ajustements, et plus tard
     * sorties) pour ce produit.
     */
    public function mouvements(): HasMany
    {
        return $this->hasMany(StockMouvement::class, 'product_id', 'product_id');
    }

    /**
     * Lots encore disponibles, triés du plus ancien au plus récent : c'est
     * l'ordre exact dans lequel le FIFO doit les consommer.
     */
    public function lotsDisponibles(): HasMany
    {
        return $this->lots()
            ->where('statut', 'actif')
            ->where('quantite_restante', '>', 0)
            ->orderBy('date_entree');
    }

    /**
     * Recalcule la quantité à partir de la somme des lots actifs. Utile
     * comme garde-fou pour détecter une désynchronisation entre stocks.quantite
     * et la réalité des lots (ne remplace pas la mise à jour incrémentale
     * faite par le service de réception, qui doit rester la source normale
     * de vérité pour la performance).
     */
    public function recalculerQuantiteDepuisLots(): int
    {
        return (int) $this->lots()->where('statut', 'actif')->sum('quantite_restante');
    }
}
