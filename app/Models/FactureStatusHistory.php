<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class FactureStatusHistory extends Model
{
    protected $fillable = [
        'facture_id',
        'ancienne_valeur',
        'nouvelle_valeur',
        'changed_by',
        'commentaire',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $historique) {
            if (empty($historique->changed_by) && Auth::check()) {
                $historique->changed_by = Auth::id();
            }
        });
    }

    public function facture(): BelongsTo
    {
        return $this->belongsTo(Facture::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
