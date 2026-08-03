<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table(name: 'bon_commandes')]
class BonCommande extends Model
{
    protected $fillable = [
        'numero',
        'commande_id',
        'code_fournisseur',
        'numero_compte',
        'date_commande',
        'date_livraison',
        'magasin_facturation_id',
        'magasin_livraison_id',
        'montant_commande',
        'statut_commande',
        'created_by',
    ];

    protected $casts = [
        'date_commande' => 'date',
        'date_livraison' => 'date',
        'montant_commande' => 'decimal:2',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function magasinFacturation(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_facturation_id');
    }

    public function magasinLivraison(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_livraison_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
