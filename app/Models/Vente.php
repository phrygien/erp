<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class Vente extends Model
{
    protected $fillable = [
        'product_id',
        'magasin_id',
        'stock_lot_id',
        'canal_vente',
        'caisse_session_id',
        'quantite',
        'montant_total_ht_vente',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'montant_total_ht_vente' => 'decimal:2',
    ];

    public const CANAL_EN_LIGNE = 'en_ligne';
    public const CANAL_CAISSE = 'caisse';

    public const CANAUX = [
        self::CANAL_EN_LIGNE,
        self::CANAL_CAISSE,
    ];

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
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function stockLot(): BelongsTo
    {
        return $this->belongsTo(StockLot::class);
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
}
