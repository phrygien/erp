<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'detail_commandes')]
class DetailCommande extends Model
{
    protected $fillable = [
        'pu_achat_HT',
        'tax',
        'taux_remise',
        'pu_achat_net',
        'commande_id',
        'product_id',
        'quantite',
    ];

    protected $casts = [
        'pu_achat_HT' => 'decimal:2',
        'tax' => 'decimal:2',
        'taux_remise' => 'decimal:2',
        'pu_achat_net' => 'decimal:2',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function repartitions(): HasMany
    {
        return $this->hasMany(RepartitionDetailCommande::class, 'detail_commande_id');
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (DetailCommande $detail) {
            $ht = (float) $detail->pu_achat_HT;
            $tax = (float) $detail->tax;
            $remise = (float) $detail->taux_remise;

            $detail->pu_achat_net = $ht + ($ht * $tax / 100) - ($ht * $remise / 100);
        });

        static::saved(fn (DetailCommande $detail) => $detail->commande?->updateMontantTotal());
        static::deleted(fn (DetailCommande $detail) => $detail->commande?->updateMontantTotal());
    }

    public function updateQuantiteFromRepartitions(): void
    {
        $this->update(['quantite' => $this->repartitions()->sum('quantite')]);
    }
}
