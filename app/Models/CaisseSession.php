<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
     * Clôture la session : fige le comptage physique du responsable, calcule
     * l'écart par rapport au solde théorique déjà connu à ce moment (à
     * calculer en amont par le service qui gère les mouvements de caisse,
     * ce modèle ne les connaît pas), et passe le statut à fermée.
     *
     * Le solde théorique doit être fourni par l'appelant plutôt que
     * recalculé ici, car CaisseSession n'a pas connaissance des mouvements
     * de caisse (encaissements/décaissements) — cette responsabilité
     * reviendra à un futur CaisseMouvement / service dédié.
     */
    public function fermer(float $soldeTheorique, float $soldeReel, ?string $commentaire = null): void
    {
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
}
