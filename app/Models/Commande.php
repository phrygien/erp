<?php

namespace App\Models;

use App\Observers\CommandeObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;

#[Table(name: 'commandes')]
#[ObservedBy(CommandeObserver::class)]
class Commande extends Model
{
    use Notifiable;

    protected $fillable = [
        'numero_commande',
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

    public function statusHistories(): HasMany
    {
        return $this->hasMany(CommandeStatusHistorique::class);
    }

    public function updateMontantTotal(): void
    {
        $total = $this->detailCommandes()
            ->get()
            ->sum(fn (DetailCommande $d) => (float) $d->pu_achat_net * (float) $d->quantite);

        $this->update(['montant_total' => $total]);
    }

    public function bonCommande(): HasOne
    {
        return $this->hasOne(BonCommande::class);
    }

    /**
     * Historique des modifications de quantité sur toutes les répartitions de
     * cette commande, tous produits et magasins confondus. Repose sur
     * repartition_detailcommandes.commande_id, dénormalisé pour permettre ce
     * hasManyThrough en un seul saut (la chaîne réelle a 3 niveaux :
     * Commande -> DetailCommande -> RepartitionDetailcommande -> Historique).
     */
    public function repartitionHistoriques(): HasManyThrough
    {
        return $this->hasManyThrough(
            RepartitionDetailcommandeHistorique::class,
            RepartitionDetailcommande::class,
            'commande_id',                  // FK sur repartition_detailcommandes -> commandes.id
            'repartition_detailcommande_id', // FK sur l'historique -> repartition_detailcommandes.id
            'id',                            // clé locale sur commandes
            'id'                             // clé locale sur repartition_detailcommandes
        );
    }

    /**
     * Répercute l'état courant de la commande sur son bon de commande, s'il en
     * existe un. Ne crée jamais de bon de commande (voir ViewCommande pour la
     * génération initiale, qui nécessite numero/dates/etc.).
     */
    public function syncBonCommande(): void
    {
        $bonCommande = $this->bonCommande;

        if (! $bonCommande) {
            return;
        }

        $bonCommande->update([
            'montant_commande' => $this->montant_total,
            'statut_commande' => $this->statut_commande,
            'code_fournisseur' => $this->fournisseur?->code,
            'magasin_facturation_id' => $this->magasin_id,
            'magasin_livraison_id' => $this->magasin_id,
        ]);
    }
}
