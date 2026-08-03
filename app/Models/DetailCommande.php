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
}
