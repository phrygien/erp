<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailFacture extends Model
{
    use HasFactory;

    protected $fillable = [
        'facture_id',
        'detail_commande_id',
        'quantite_commande',
        'quantite_facturee',
        'prix_unitaire_ht',
        'montant_ht',
        'montant_remise',
        'montant_final_ht',
        'montant_final_net',
    ];

    protected $casts = [
        'quantite_commande'  => 'integer',
        'quantite_facturee'  => 'integer',
        'prix_unitaire_ht'   => 'decimal:2',
        'montant_ht'         => 'decimal:2',
        'montant_remise'     => 'decimal:2',
        'montant_final_ht'   => 'decimal:2',
        'montant_final_net'  => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------
    */

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    public function detailCommande(): BelongsTo
    {
        return $this->belongsTo(DetailCommande::class);
    }

    /*
    |--------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------
    */

    /**
     * Écart entre quantité commandée et quantité réellement facturée.
     * Positif = sous-facturé, négatif = sur-facturé.
     */
    public function ecartQuantite(): int
    {
        return $this->quantite_commande - $this->quantite_facturee;
    }

    public function aEcartQuantite(): bool
    {
        return $this->ecartQuantite() !== 0;
    }

    /**
     * Recalcule les montants de la ligne à partir du prix unitaire,
     * de la quantité facturée et de la remise.
     */
    public function recalculerMontants(): void
    {
        $this->montant_ht = round($this->quantite_facturee * $this->prix_unitaire_ht, 2);
        $this->montant_final_ht = round($this->montant_ht - $this->montant_remise, 2);
        // Si tu appliques la TVA au niveau ligne, ajoute le calcul ici.
        // Sinon montant_final_net reste égal à montant_final_ht par défaut.
        $this->montant_final_net = $this->montant_final_ht;
    }

    /*
    |--------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        // Recalcule automatiquement le total de la facture parente
        // à chaque création/modif/suppression d'une ligne
        static::saved(function (DetailFacture $detail) {
            $detail->facture?->recalculerMontants();
        });

        static::deleted(function (DetailFacture $detail) {
            $detail->facture?->recalculerMontants();
        });
    }
}
