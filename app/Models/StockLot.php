<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class StockLot extends Model
{
    protected $fillable = [
        'product_id',
        'reception_commande_id',
        'detail_reception_commande_id',
        'quantite_initiale',
        'quantite_restante',
        'pu_achat_net',
        'date_entree',
        'statut',
    ];

    protected $casts = [
        'quantite_initiale' => 'integer',
        'quantite_restante' => 'integer',
        'pu_achat_net' => 'decimal:2',
        'date_entree' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function receptionCommande(): BelongsTo
    {
        return $this->belongsTo(ReceptionCommande::class);
    }

    public function detailReceptionCommande(): BelongsTo
    {
        return $this->belongsTo(DetailReceptionCommande::class);
    }

    /**
     * Mouvements (entrée qui a créé ce lot, sorties qui l'ont entamé, plus
     * tard) rattachés à ce lot précis.
     */
    public function mouvements(): HasMany
    {
        return $this->hasMany(StockMouvement::class);
    }

    /**
     * Lots encore utilisables, du plus ancien au plus récent — l'ordre
     * exact de consommation en FIFO.
     */
    public function scopeDisponibles(Builder $query): Builder
    {
        return $query->where('statut', 'actif')
            ->where('quantite_restante', '>', 0)
            ->orderBy('date_entree');
    }

    public function scopePourProduit(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function estDisponible(): bool
    {
        return $this->statut === 'actif' && $this->quantite_restante > 0;
    }

    /**
     * Consomme jusqu'à $quantite unités de ce lot (pour une vente future).
     * Ne consomme jamais plus que ce qui reste réellement disponible :
     * retourne la quantité effectivement prélevée, à additionner par
     * l'appelant qui parcourt plusieurs lots en FIFO jusqu'à couvrir la
     * quantité totale demandée.
     *
     * Ne crée pas le StockMouvement associé : cette responsabilité revient
     * au service appelant, qui a le contexte (vente, ajustement) nécessaire
     * pour renseigner correctement le mouvement.
     */
    public function consommer(int $quantite): int
    {
        $prelevee = min($quantite, $this->quantite_restante);

        if ($prelevee <= 0) {
            return 0;
        }

        $this->quantite_restante -= $prelevee;

        if ($this->quantite_restante <= 0) {
            $this->statut = 'epuise';
        }

        $this->save();

        return $prelevee;
    }
}
