<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'repartition_detailcommande_historiques')]
class RepartitionDetailcommandeHistorique extends Model
{
    protected $fillable = [
        'repartition_detailcommande_id',
        'champ',
        'ancienne_valeur',
        'nouvelle_valeur',
        'changed_by',
    ];

    public function repartition(): BelongsTo
    {
        return $this->belongsTo(RepartitionDetailcommande::class, 'repartition_detailcommande_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
