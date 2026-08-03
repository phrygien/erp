<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'products')]
class Product extends Model
{
    protected $fillable = [
        'product_code',
        'category_id',
        'marque_id',
        'type_id',
        'ligne_id',
        'designation',
        'designation_variant',
        'article',
        'ref_fabri_n_1',
        'EAN',
        'pght_parkod',
        'tva',
        'devise',
        'hs_code',
        'statut_parkod',
        'state',
    ];

    public function marque(): BelongsTo
    {
        return $this->belongsTo(Marque::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function ligne(): BelongsTo
    {
        return $this->belongsTo(Ligne::class);
    }
}
