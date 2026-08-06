<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_facture',
        'libelle_facture',
        'bon_commande_id',
        'fournisseur_id',
        'date_facture',
        'date_echeance',
        'montant_ht',
        'taux_tva',
        'montant_tva',
        'remise',
        'montant_ttc',
        'type',
        'statut',
        'archivage',
        'created_by',
    ];

    protected $casts = [
        'date_facture'   => 'date',
        'date_echeance'  => 'date',
        'montant_ht'     => 'decimal:2',
        'taux_tva'       => 'decimal:2',
        'montant_tva'    => 'decimal:2',
        'remise'         => 'decimal:2',
        'montant_ttc'    => 'decimal:2',
        'archivage'      => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------
    */

    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function bonCommande(): BelongsTo
    {
        return $this->belongsTo(BonCommande::class);
    }

    public function detailFactures(): HasMany
    {
        return $this->hasMany(DetailFacture::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(FactureStatusHistory::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------
    */

    public function scopeEnCours(Builder $query): Builder
    {
        return $query->where('statut', 'encours');
    }

    public function scopePayees(Builder $query): Builder
    {
        return $query->where('statut', 'paye');
    }

    public function scopeRejetees(Builder $query): Builder
    {
        return $query->where('statut', 'rejete');
    }

    public function scopeArchivees(Builder $query): Builder
    {
        return $query->where('archivage', true);
    }

    public function scopeNonArchivees(Builder $query): Builder
    {
        return $query->where('archivage', false);
    }

    /*
    |--------------------------------------------------------------------
    | Accessors / Helpers
    |--------------------------------------------------------------------
    */

    public function estPayee(): bool
    {
        return $this->statut === 'paye';
    }

    public function estRejetee(): bool
    {
        return $this->statut === 'rejete';
    }

    public function estAvoir(): bool
    {
        return $this->type === 'retour_commande';
    }

    /**
     * Recalcule et met à jour les montants ttc/tva à partir des lignes
     * de detail_factures. À appeler après ajout/modif/suppression d'une ligne.
     */
    public function recalculerMontants(): void
    {
        $this->montant_ht = $this->detailFactures()->sum('montant_final_ht');
        $this->montant_tva = $this->montant_ht * ($this->taux_tva / 100);
        $this->montant_ttc = $this->montant_ht + $this->montant_tva - $this->remise;
        $this->save();
    }

    /*
    |--------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        // Journalise automatiquement chaque changement de statut
        static::updating(function (Facture $facture) {
            if ($facture->isDirty('statut')) {
                $facture->statusHistories()->create([
                    'ancienne_valeur' => $facture->getOriginal('statut'),
                    'nouvelle_valeur' => $facture->statut,
                    'changed_by'      => auth()->id(),
                ]);
            }
        });
    }
}
