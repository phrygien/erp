<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

#[Table(name: 'commandes')]
class Commande extends Model
{
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

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Commande $commande) {
            if (empty($commande->numero_commande)) {
                $commande->numero_commande = static::generateNumeroCommande();
            }
        });

        static::updating(function (Commande $commande) {
            $champsSuivis = ['etat_commande', 'statut_commande'];

            foreach ($champsSuivis as $champ) {
                if ($commande->isDirty($champ)) {
                    $commande->statusHistories()->create([
                        'champ' => $champ,
                        'ancienne_valeur' => $commande->getOriginal($champ),
                        'nouvelle_valeur' => $commande->{$champ},
                        'changed_by' => Auth::id(),
                    ]);
                }
            }
        });
    }

    protected static function generateNumeroCommande(): string
    {
        do {
            $numero = strtoupper(Str::random(5));
        } while (static::query()->where('numero_commande', $numero)->exists());

        return $numero;
    }

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
        return $this->hasMany(CommandeStatusHistorique::class);
    }

    public function updateMontantTotal(): void
    {
        $total = $this->detailCommandes()
            ->get()
            ->sum(fn (DetailCommande $d) => (float) $d->pu_achat_net * (float) $d->quantite);

        $this->update(['montant_total' => $total]);
    }
}
