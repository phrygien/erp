<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

#[Table(name: 'repartition_detailcommandes')]
class RepartitionDetailcommande extends Model
{
    protected $fillable = [
        'detail_commande_id',
        'commande_id',
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

            if (empty($repartition->commande_id) && $repartition->detail_commande_id) {
                $repartition->commande_id = DetailCommande::query()
                    ->whereKey($repartition->detail_commande_id)
                    ->value('commande_id');
            }
        });

        static::updating(function (self $repartition) {
            if ($repartition->isDirty('quantite')) {
                $repartition->historiques()->create([
                    'champ' => 'quantite',
                    'ancienne_valeur' => $repartition->getOriginal('quantite'),
                    'nouvelle_valeur' => $repartition->quantite,
                    'changed_by' => Auth::id(),
                ]);
            }
        });

        static::created(function (self $repartition) {
            $repartition->historiques()->create([
                'champ' => 'quantite',
                'ancienne_valeur' => null,
                'nouvelle_valeur' => $repartition->quantite,
                'changed_by' => $repartition->created_by,
            ]);
        });

        static::saved(fn (self $r) => $r->detailCommande?->updateQuantiteFromRepartitions());
        static::deleted(fn (self $r) => $r->detailCommande?->updateQuantiteFromRepartitions());
    }

    public function detailCommande(): BelongsTo
    {
        return $this->belongsTo(DetailCommande::class, 'detail_commande_id');
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function historiques(): HasMany
    {
        return $this->hasMany(RepartitionDetailcommandeHistorique::class, 'repartition_detailcommande_id');
    }
}
