<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ReceptionCommande extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_reception',
        'commande_id',
        'bon_commande_id',
        'numero_bl',
        'date_reception',
        'statut',
        'commentaire',
        'received_by',
    ];

    protected $casts = [
        'date_reception' => 'date',
    ];

    /**
     * Statuts possibles (à garder en phase avec l'enum de la migration).
     */
    public const STATUT_EN_COURS = 'en_cours';
    public const STATUT_PARTIELLE = 'partielle';
    public const STATUT_COMPLETE = 'complete';
    public const STATUT_ANNULEE = 'annulee';

    public const STATUTS = [
        self::STATUT_EN_COURS,
        self::STATUT_PARTIELLE,
        self::STATUT_COMPLETE,
        self::STATUT_ANNULEE,
    ];

    /**
     * Préfixes utilisés pour la génération des numéros.
     */
    protected const NUMERO_PREFIX = 'REC';
    protected const NUMERO_BL_PREFIX = 'BL';

    /* -----------------------------------------------------------
     | Boot
     |-----------------------------------------------------------
     */

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $reception) {
            if (empty($reception->numero_reception)) {
                $reception->numero_reception = self::genererNumeroReception();
            }

            if (empty($reception->numero_bl)) {
                $reception->numero_bl = self::genererNumeroBl();
            }
        });
    }

    /**
     * Génère un numéro de réception unique du type REC-2026-0001.
     * Utilise un verrou pessimiste pour éviter les doublons en cas
     * de créations concurrentes.
     */
    protected static function genererNumeroReception(): string
    {
        return DB::transaction(function () {
            $annee = now()->year;
            $prefix = self::NUMERO_PREFIX . '-' . $annee . '-';

            $dernier = self::where('numero_reception', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('numero_reception')
                ->first();

            $prochainNumero = 1;

            if ($dernier) {
                $dernierSequence = (int) substr($dernier->numero_reception, strlen($prefix));
                $prochainNumero = $dernierSequence + 1;
            }

            return $prefix . str_pad($prochainNumero, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Aperçu du prochain numéro de réception, sans le réserver.
     * Utilisé uniquement pour l'affichage côté formulaire.
     */
    public static function previewProchainNumero(): string
    {
        $annee = now()->year;
        $prefix = self::NUMERO_PREFIX . '-' . $annee . '-';

        $dernier = self::where('numero_reception', 'like', $prefix . '%')
            ->orderByDesc('numero_reception')
            ->first();

        $prochainNumero = 1;

        if ($dernier) {
            $dernierSequence = (int) substr($dernier->numero_reception, strlen($prefix));
            $prochainNumero = $dernierSequence + 1;
        }

        return $prefix . str_pad($prochainNumero, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Génère un numéro de bon de livraison unique du type BL-2026-0001.
     * Même logique de verrou pessimiste que pour numero_reception.
     */
    protected static function genererNumeroBl(): string
    {
        return DB::transaction(function () {
            $annee = now()->year;
            $prefix = self::NUMERO_BL_PREFIX . '-' . $annee . '-';

            $dernier = self::where('numero_bl', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('numero_bl')
                ->first();

            $prochainNumero = 1;

            if ($dernier) {
                $dernierSequence = (int) substr($dernier->numero_bl, strlen($prefix));
                $prochainNumero = $dernierSequence + 1;
            }

            return $prefix . str_pad($prochainNumero, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Aperçu du prochain numéro de BL, sans le réserver.
     * Utilisé uniquement pour l'affichage côté formulaire.
     */
    public static function previewProchainNumeroBl(): string
    {
        $annee = now()->year;
        $prefix = self::NUMERO_BL_PREFIX . '-' . $annee . '-';

        $dernier = self::where('numero_bl', 'like', $prefix . '%')
            ->orderByDesc('numero_bl')
            ->first();

        $prochainNumero = 1;

        if ($dernier) {
            $dernierSequence = (int) substr($dernier->numero_bl, strlen($prefix));
            $prochainNumero = $dernierSequence + 1;
        }

        return $prefix . str_pad($prochainNumero, 4, '0', STR_PAD_LEFT);
    }

    /* -----------------------------------------------------------
     | Relations
     |-----------------------------------------------------------
     */

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function bonCommande(): BelongsTo
    {
        return $this->belongsTo(BonCommande::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailReceptionCommande::class);
    }

    /* -----------------------------------------------------------
     | Accessors / Helpers
     |-----------------------------------------------------------
     */

    public function getQteTotaleRecueAttribute(): int
    {
        return $this->details()->sum('qte_recue');
    }

    public function getQteTotaleInvendableAttribute(): int
    {
        return $this->details()->sum('qte_invendable');
    }

    public function estComplete(): bool
    {
        return $this->statut === self::STATUT_COMPLETE;
    }

    /* -----------------------------------------------------------
     | Scopes
     |-----------------------------------------------------------
     */

    public function scopeEnCours($query)
    {
        return $query->where('statut', self::STATUT_EN_COURS);
    }

    public function scopePourCommande($query, int $commandeId)
    {
        return $query->where('commande_id', $commandeId);
    }
}
