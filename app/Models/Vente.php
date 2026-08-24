<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Vente extends Model
{
    protected $fillable = [
        'numero_vente',
        'magasin_id',
        'canal_vente',
        'caisse_session_id',
        'montant_total',
    ];

    protected $casts = [
        'montant_total' => 'decimal:2',
    ];

    public const CANAL_EN_LIGNE = 'en_ligne';
    public const CANAL_CAISSE = 'caisse';

    public const CANAUX = [
        self::CANAL_EN_LIGNE,
        self::CANAL_CAISSE,
    ];

    /** Nombre de tentatives en cas de collision sur numero_vente (course concurrente). */
    private const MAX_TENTATIVES_NUMERO = 5;

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $vente) {
            // Cohérence canal <-> session : une vente en caisse doit
            // pouvoir être rattachée à la session qui l'a enregistrée
            // (traçabilité du responsable et de la journée), une vente en
            // ligne ne passe par aucune session physique.
            if ($vente->canal_vente === self::CANAL_CAISSE && empty($vente->caisse_session_id)) {
                throw ValidationException::withMessages([
                    'caisse_session_id' => 'Une vente en caisse doit être rattachée à une session de caisse.',
                ]);
            }

            if ($vente->canal_vente === self::CANAL_EN_LIGNE && ! empty($vente->caisse_session_id)) {
                throw ValidationException::withMessages([
                    'caisse_session_id' => 'Une vente en ligne ne peut pas être rattachée à une session de caisse.',
                ]);
            }
        });

        // Numéro attribué uniquement s'il n'a pas déjà été renseigné
        // explicitement (permet de forcer un numéro précis si besoin, par
        // exemple lors d'un import ou d'une correction manuelle).
        static::creating(function (self $vente) {
            if (empty($vente->numero_vente)) {
                $vente->numero_vente = self::genererNumeroVente($vente->canal_vente);
            }
        });
    }

    /**
     * Lignes de la vente (un produit par ligne, une quantité, un prix).
     * C'est désormais la seule source du détail d'une transaction :
     * product_id/stock_lot_id/quantite ne sont plus des colonnes de
     * ventes, elles vivent sur details_ventes.
     */
    public function details(): HasMany
    {
        return $this->hasMany(DetailVente::class);
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function caisseSession(): BelongsTo
    {
        return $this->belongsTo(CaisseSession::class);
    }

    public function estEnLigne(): bool
    {
        return $this->canal_vente === self::CANAL_EN_LIGNE;
    }

    public function estEnCaisse(): bool
    {
        return $this->canal_vente === self::CANAL_CAISSE;
    }

    public function scopeEnLigne($query)
    {
        return $query->where('canal_vente', self::CANAL_EN_LIGNE);
    }

    public function scopeEnCaisse($query)
    {
        return $query->where('canal_vente', self::CANAL_CAISSE);
    }

    public function scopePourSession($query, int $caisseSessionId)
    {
        return $query->where('caisse_session_id', $caisseSessionId);
    }

    /**
     * Recalcule montant_total à partir de la somme des lignes détail.
     * Utile après un ajustement manuel d'une ligne, ou comme garde-fou de
     * cohérence (ex : commande artisan de vérification périodique).
     */
    public function recalculerMontantTotal(): float
    {
        $total = (float) $this->details()->sum('montant_total_ligne');

        $this->update(['montant_total' => $total]);

        return $total;
    }

    /**
     * Génère un numéro de vente lisible, du type VC-20260820-00001
     * (VC = vente caisse, VL = vente en ligne), unique par jour et par
     * canal.
     *
     * La séquence repart de 1 chaque jour. En cas de collision provoquée
     * par deux ventes concurrentes calculant le même prochain numéro au
     * même instant, la contrainte unique en base fait échouer l'insertion
     * (QueryException) et on retente avec un nouveau numéro, jusqu'à
     * MAX_TENTATIVES_NUMERO fois.
     */
    public static function genererNumeroVente(string $canalVente): string
    {
        $prefixe = $canalVente === self::CANAL_EN_LIGNE ? 'VL' : 'VC';
        $jour = now()->format('Ymd');

        return DB::transaction(function () use ($prefixe, $jour) {
            $dernierNumero = self::query()
                ->where('numero_vente', 'like', "{$prefixe}-{$jour}-%")
                ->lockForUpdate()
                ->orderByDesc('numero_vente')
                ->value('numero_vente');

            $prochaineSequence = 1;

            if ($dernierNumero) {
                $segments = explode('-', $dernierNumero);
                $prochaineSequence = ((int) end($segments)) + 1;
            }

            return sprintf('%s-%s-%05d', $prefixe, $jour, $prochaineSequence);
        });
    }

    /**
     * Crée la vente en garantissant l'unicité du numero_vente même en cas
     * de forte concurrence (plusieurs caisses qui valident au même
     * instant). À utiliser à la place de Vente::create(...) dans les flux
     * sensibles (caisse, checkout en ligne).
     */
    public static function creerAvecNumeroUnique(array $attributs): self
    {
        $tentative = 0;

        while (true) {
            $tentative++;

            try {
                return self::create($attributs);
            } catch (QueryException $exception) {
                $estCollisionNumeroVente = str_contains($exception->getMessage(), 'numero_vente');

                if (! $estCollisionNumeroVente || $tentative >= self::MAX_TENTATIVES_NUMERO) {
                    throw $exception;
                }

                // On force une régénération au tour suivant en retirant le
                // numéro qui vient d'entrer en collision.
                unset($attributs['numero_vente']);
            }
        }
    }
}
