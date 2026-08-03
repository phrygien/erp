<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

#[Table(name: 'repartition_detailcommandes')]
class RepartitionDetailcommande extends Model
{
    protected $fillable = [
        'detail_commande_id',
        'magasin_id',
        'quantite',
        'created_by',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $repartition) {
            if (empty($repartition->created_by) && Auth::check()) {
                $repartition->created_by = Auth::id();
            }
        });

        static::saved(fn (self $r) => $r->detailCommande?->updateQuantiteFromRepartitions());
        static::deleted(fn (self $r) => $r->detailCommande?->updateQuantiteFromRepartitions());
    }

    public function detailCommande(): BelongsTo
    {
        return $this->belongsTo(DetailCommande::class, 'detail_commande_id');
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
