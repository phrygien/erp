<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandeStatusHistorique extends Model
{
    /**
     * Le nom de la table ne suit pas la convention standard
     * générée à partir du nom du modèle (commande_status_histories
     * au lieu de commande_status_historiques).
     */
    protected $table = 'commande_status_histories';

    protected $fillable = [
        'commande_id',
        'champ',
        'ancienne_valeur',
        'nouvelle_valeur',
        'changed_by',
        'commentaire',
    ];

    protected $casts = [
        'commande_id' => 'integer',
        'changed_by' => 'integer',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
