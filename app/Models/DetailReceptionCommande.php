<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailReceptionCommande extends Model
{
    use HasFactory;

    protected $fillable = [
        'reception_commande_id',
        'detail_commande_id',
        'product_id',
        'qte_recue',
        'qte_invendable',
        'motif_invendable',
        'commentaire',
    ];

    protected $casts = [
        'qte_recue' => 'integer',
        'qte_invendable' => 'integer',
    ];

    /* -----------------------------------------------------------
     | Relations
     |-----------------------------------------------------------
     */

    public function receptionCommande(): BelongsTo
    {
        return $this->belongsTo(ReceptionCommande::class);
    }

    public function detailCommande(): BelongsTo
    {
        return $this->belongsTo(DetailCommande::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /* -----------------------------------------------------------
     | Accessors / Helpers
     |-----------------------------------------------------------
     */

    public function getQteBonneAttribute(): int
    {
        return $this->qte_recue - $this->qte_invendable;
    }

    /* -----------------------------------------------------------
     | Events
     |-----------------------------------------------------------
     */

    protected static function booted(): void
    {
        static::saving(function (DetailReceptionCommande $detail) {
            if (! $detail->product_id && $detail->detail_commande_id) {
                $detail->product_id = DetailCommande::query()
                    ->whereKey($detail->detail_commande_id)
                    ->value('product_id');
            }
        });
    }
}
