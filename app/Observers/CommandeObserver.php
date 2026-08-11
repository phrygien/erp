<?php

namespace App\Observers;

use App\Models\Commande;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CommandeObserver
{
    /**
     * IDs de commande déjà notifiées pour "updated" durant cette requête,
     * pour éviter les doublons quand une seule action utilisateur déclenche
     * plusieurs sauvegardes en cascade (ex : updateMontantTotal() appelé
     * une fois par ligne de détail modifiée).
     */
    protected static array $notifiedUpdated = [];

    /**
     * Génère le numéro de commande avant la création si absent.
     */
    public function creating(Commande $commande): void
    {
        if (empty($commande->numero_commande)) {
            $commande->numero_commande = $this->generateNumeroCommande();
        }
    }

    /**
     * Historise les changements d'état/statut avant la sauvegarde de la mise à jour.
     */
    public function updating(Commande $commande): void
    {
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
    }

    /**
     * Handle the Commande "created" event.
     */
    public function created(Commande $commande): void
    {
        $recipient = auth()->user();

        if (! $recipient) {
            return;
        }

        Notification::make()
            ->title('Création nouvelle commande')
            ->body("La commande {$commande->numero_commande} vient d'être créée")
            ->sendToDatabase($recipient);
    }

    /**
     * Point central : dès que le montant, le statut, le fournisseur ou le
     * magasin de la commande change (peu importe d'où vient la sauvegarde :
     * wizard d'édition, RelationManager, recalcul automatique via les
     * DetailCommande...), on répercute sur le bon de commande lié s'il existe.
     */
    public function updated(Commande $commande): void
    {
        if ($commande->isDirty(['montant_total', 'statut_commande', 'fournisseur_id', 'magasin_id'])) {
            $commande->syncBonCommande();
        }

        if ($commande->isDirty('statut_commande')) {
            $this->notifyStatusChange($commande);
        }

        $this->notifyUpdatedOnce($commande);
    }

    /**
     * Envoie la notification "Modification commande" une seule fois par
     * commande et par requête, même si plusieurs sauvegardes en cascade
     * (ex : recalcul du montant via les lignes de détail) déclenchent
     * "updated" plusieurs fois de suite.
     */
    protected function notifyUpdatedOnce(Commande $commande): void
    {
        if (isset(static::$notifiedUpdated[$commande->id])) {
            return;
        }

        static::$notifiedUpdated[$commande->id] = true;

        $recipient = auth()->user();

        if (! $recipient) {
            return;
        }

        Notification::make()
            ->title('Modification commande')
            ->body("La commande {$commande->numero_commande} vient d'être modifiée")
            ->sendToDatabase($recipient);
    }

    /**
     * Notifie le créateur de la commande (si différent de l'utilisateur qui
     * vient d'effectuer le changement) du nouveau statut, via les
     * notifications base de données de Filament.
     */
    protected function notifyStatusChange(Commande $commande): void
    {
        $recipient = $commande->createdBy;

        if (! $recipient || $recipient->id === Auth::id()) {
            return;
        }

        Notification::make()
            ->title('Statut de commande modifié')
            ->body(sprintf(
                'La commande %s est passée de "%s" à "%s".',
                $commande->numero_commande,
                $commande->getOriginal('statut_commande'),
                $commande->statut_commande,
            ))
            ->sendToDatabase($recipient);
    }

    /**
     * Handle the Commande "deleted" event.
     */
    public function deleted(Commande $commande): void
    {
        $recipient = auth()->user();

        if (! $recipient) {
            return;
        }

        Notification::make()
            ->title('Suppression commande')
            ->body("La commande {$commande->numero_commande} vient d'être supprimée")
            ->sendToDatabase($recipient);
    }

    /**
     * Handle the Commande "restored" event.
     */
    public function restored(Commande $commande): void
    {
        $recipient = auth()->user();

        if (! $recipient) {
            return;
        }

        Notification::make()
            ->title('Restauration commande')
            ->body("La commande {$commande->numero_commande} vient d'être restaurée")
            ->sendToDatabase($recipient);
    }

    /**
     * Handle the Commande "force deleted" event.
     */
    public function forceDeleted(Commande $commande): void
    {
        $recipient = auth()->user();

        if (! $recipient) {
            return;
        }

        Notification::make()
            ->title('Suppression définitive commande')
            ->body("La commande {$commande->numero_commande} vient d'être supprimée définitivement")
            ->sendToDatabase($recipient);
    }

    protected function generateNumeroCommande(): string
    {
        do {
            $numero = strtoupper(\Illuminate\Support\Str::random(5));
        } while (Commande::query()->where('numero_commande', $numero)->exists());

        return $numero;
    }
}
