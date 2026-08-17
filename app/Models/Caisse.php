<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caisse extends Model
{
    protected $fillable = [
        'numero_caisse',
        'name',
        'magasin_id',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CaisseSession::class);
    }

    /**
     * La session actuellement ouverte sur cette caisse, s'il y en a une.
     * Une caisse ne devrait jamais avoir plus d'une session ouverte à la
     * fois (voir la contrainte à ajouter en base).
     */
    public function sessionOuverte(): ?CaisseSession
    {
        return $this->sessions()
            ->where('statut', 'ouverte')
            ->latest('ouverte_le')
            ->first();
    }

    public function aUneSessionOuverte(): bool
    {
        return $this->sessions()->where('statut', 'ouverte')->exists();
    }
}
