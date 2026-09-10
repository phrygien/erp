<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class CaisseSession extends Model
{
    protected $fillable = [
        'caisse_id',
        'responsable_id',
        'date_session',
        'ouverte_le',
        'fermee_le',
        'solde_ouverture',
        'solde_cloture_theorique',
        'solde_cloture_reel',
        'ecart',
        'statut',
        'commentaire',
    ];

    protected $casts = [
        'date_session' => 'date',
        'ouverte_le' => 'datetime',
        'fermee_le' => 'datetime',
        'solde_ouverture' => 'decimal:2',
        'solde_cloture_theorique' => 'decimal:2',
        'solde_cloture_reel' => 'decimal:2',
        'ecart' => 'decimal:2',
    ];

    public const STATUT_OUVERTE = 'ouverte';
    public const STATUT_FERMEE = 'fermee';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $session) {
            if (empty($session->responsable_id) && Auth::check()) {
                $session->responsable_id = Auth::id();
            }

            if (empty($session->ouverte_le)) {
                $session->ouverte_le = now();
            }

            if (empty($session->date_session)) {
                $session->date_session = $session->ouverte_le->toDateString();
            }
        });
    }

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function estOuverte(): bool
    {
        return $this->statut === self::STATUT_OUVERTE;
    }

    /**
     * Solde théorique attendu en caisse à l'instant présent : solde
     * d'ouverture + somme des ventes en caisse rattachées à cette session.
     *
     * Ne prend en compte que les ventes au canal "caisse" (les ventes en
     * ligne ne transitent jamais par une session physique, voir
     * Vente::CANAL_EN_LIGNE / la contrainte dans Vente::boot()).
     */
    public function calculerSoldeTheorique(): float
    {
        $totalVentes = (float) $this->ventes()
            ->enCaisse()
            ->sum('montant_total');

        return (float) $this->solde_ouverture + $totalVentes;
    }

    /**
     * Clôture la session : calcule automatiquement le solde théorique à
     * partir des ventes de la session, fige le comptage physique fourni
     * par le responsable, calcule l'écart entre les deux, et passe le
     * statut à fermée.
     */
    public function fermer(float $soldeReel, ?string $commentaire = null): void
    {
        $soldeTheorique = $this->calculerSoldeTheorique();

        $this->update([
            'solde_cloture_theorique' => $soldeTheorique,
            'solde_cloture_reel' => $soldeReel,
            'ecart' => $soldeReel - $soldeTheorique,
            'statut' => self::STATUT_FERMEE,
            'fermee_le' => now(),
            'commentaire' => $commentaire ?? $this->commentaire,
        ]);
    }

    public function scopeOuvertes($query)
    {
        return $query->where('statut', self::STATUT_OUVERTE);
    }

    public function scopePourCaisse($query, int $caisseId)
    {
        return $query->where('caisse_id', $caisseId);
    }

    public function scopePourDate($query, string $date)
    {
        return $query->whereDate('date_session', $date);
    }

    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class, 'caisse_session_id');
    }
}
