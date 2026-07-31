<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('lignes')]
#[Fillable(['code', 'name', 'category_id', 'marque_id' ,'state'])]
class Ligne extends Model
{
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function marque(): BelongsTo
    {
        return $this->belongsTo(Marque::class);
    }
}
