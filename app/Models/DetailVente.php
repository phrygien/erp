<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailVente extends Model
{
    protected $table = 'details_ventes';

    protected $fillable = [
        'vente_id',
        'product_id',
        'stock_lot_id',
        'quantite',
        'prix_unitaire',
        'montant_total_ligne',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_unitaire' => 'decimal:2',
        'montant_total_ligne' => 'decimal:2',
    ];

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockLot(): BelongsTo
    {
        return $this->belongsTo(StockLot::class);
    }
}
