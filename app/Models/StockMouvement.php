<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StockMouvement extends Model
{
    protected $fillable = [
        'product_id',
        'stock_lot_id',
        'type',
        'quantite',
        'quantite_avant',
        'quantite_apres',
        'date_mouvement',
        'reception_commande_id',
        'detail_reception_commande_id',
        'commentaire',
        'created_by',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'quantite_avant' => 'integer',
        'quantite_apres' => 'integer',
        'date_mouvement' => 'date',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $mouvement) {
            if (empty($mouvement->created_by) && Auth::check()) {
                $mouvement->created_by = Auth::id();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockLot(): BelongsTo
    {
        return $this->belongsTo(StockLot::class);
    }

    public function receptionCommande(): BelongsTo
    {
        return $this->belongsTo(ReceptionCommande::class);
    }

    public function detailReceptionCommande(): BelongsTo
    {
        return $this->belongsTo(DetailReceptionCommande::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeEntrees(Builder $query): Builder
    {
        return $query->where('type', 'entree');
    }

    public function scopeAjustements(Builder $query): Builder
    {
        return $query->where('type', 'ajustement');
    }

    public function scopePourProduit(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Enregistre un mouvement d'entrée (réception) et met à jour la
     * quantité du Stock global en une seule opération cohérente. Ne crée
     * pas le StockLot associé : celui-ci doit déjà exister (ou être créé
     * juste avant par le service de réception), qui seul connaît le coût
     * unitaire, la date d'entrée, etc.
     *
     * @param  Stock  $stock  Le Stock du produit concerné, verrouillé par
     *                        l'appelant si nécessaire (ex: lockForUpdate()
     *                        dans une transaction) pour éviter les
     *                        conditions de concurrence entre deux
     *                        réceptions simultanées du même produit.
     */
    public static function enregistrerEntree(
        Stock $stock,
        int $quantite,
        ?StockLot $stockLot = null,
        ?int $receptionCommandeId = null,
        ?int $detailReceptionCommandeId = null,
        ?string $dateMouvement = null,
        ?string $commentaire = null,
    ): self {
        $avant = $stock->quantite;
        $apres = $avant + $quantite;

        $stock->update(['quantite' => $apres]);

        return self::create([
            'product_id' => $stock->product_id,
            'stock_lot_id' => $stockLot?->id,
            'type' => 'entree',
            'quantite' => $quantite,
            'quantite_avant' => $avant,
            'quantite_apres' => $apres,
            'date_mouvement' => $dateMouvement ?? now()->toDateString(),
            'reception_commande_id' => $receptionCommandeId,
            'detail_reception_commande_id' => $detailReceptionCommandeId,
            'commentaire' => $commentaire,
        ]);
    }

    /**
     * Corrige manuellement la quantité en stock (inventaire physique,
     * casse constatée, erreur de saisie, etc.). Contrairement à une entrée
     * ou une sortie, un ajustement n'est rattaché à aucun lot précis : il
     * corrige le total global sans chercher à savoir quel lot en est
     * responsable.
     *
     * $nouvelleQuantite est la quantité cible après correction (pas un
     * delta) : plus intuitif à saisir dans un formulaire ("le stock
     * physique est de 42") qu'un delta à calculer soi-même.
     */
    public static function enregistrerAjustement(
        Stock $stock,
        int $nouvelleQuantite,
        string $commentaire,
        ?string $dateMouvement = null,
    ): self {
        $avant = $stock->quantite;
        $delta = $nouvelleQuantite - $avant;

        $stock->update(['quantite' => $nouvelleQuantite]);

        return self::create([
            'product_id' => $stock->product_id,
            'stock_lot_id' => null,
            'type' => 'ajustement',
            'quantite' => abs($delta),
            'quantite_avant' => $avant,
            'quantite_apres' => $nouvelleQuantite,
            'date_mouvement' => $dateMouvement ?? now()->toDateString(),
            'commentaire' => $commentaire,
        ]);
    }
}
