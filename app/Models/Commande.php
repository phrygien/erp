<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table(name: 'commandes')]
class Commande extends Model
{
    protected $fillable = [
        'libelle',
        'montant_minimum',
        'montant_total',
        'remise_facture',
        'fournisseur_id',
        'magasin_id',
        'nombre_jours',
        'etat_commande',
        'statut_commande',
        'created_by',
    ];

    protected $casts = [
        'montant_minimum' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'remise_facture' => 'decimal:2',
    ];

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function detailCommandes(): HasMany
    {
        return $this->hasMany(DetailCommande::class);
    }

    public function bonCommandes(): HasMany
    {
        return $this->hasMany(BonCommande::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(CommandeStatusHistory::class);
    }
}
