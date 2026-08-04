<?php

namespace App\Filament\Resources\Commandes\Pages;

use App\Filament\Resources\Commandes\CommandeResource;
use App\Models\BonCommande;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCommande extends ViewRecord
{
    protected static string $resource = CommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleEtatCommande')
                ->label(fn () => $this->record->etat_commande === 'pre_commande'
                    ? 'Passer en commande'
                    : 'Repasser en pré-commande')
                ->icon(fn () => $this->record->etat_commande === 'pre_commande'
                    ? 'heroicon-o-check-circle'
                    : 'heroicon-o-arrow-uturn-left')
                ->color(fn () => $this->record->etat_commande === 'pre_commande'
                    ? 'success'
                    : 'gray')
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->record->etat_commande === 'pre_commande'
                    ? 'Confirmer le passage en commande'
                    : 'Repasser en pré-commande ?')
                ->modalDescription(fn () => $this->record->etat_commande === 'pre_commande'
                    ? 'Cette action générera automatiquement le bon de commande associé.'
                    : null)
                ->action(function () {
                    $newEtat = $this->record->etat_commande === 'pre_commande'
                        ? 'commande'
                        : 'pre_commande';

                    $this->record->update(['etat_commande' => $newEtat]);

                    if ($newEtat === 'commande' && ! $this->record->bonCommande) {
                        BonCommande::create([
                            'numero' => 'BC-'.now()->format('Y').'-'.str_pad($this->record->id, 5, '0', STR_PAD_LEFT),
                            'commande_id' => $this->record->id,
                            'code_fournisseur' => $this->record->fournisseur->code,
                            'date_commande' => now(),
                            'magasin_facturation_id' => $this->record->magasin_id,
                            'magasin_livraison_id' => $this->record->magasin_id,
                            'montant_commande' => $this->record->montant_total,
                            'statut_commande' => 'cree',
                            'created_by' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Bon de commande créé avec succès')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Statut mis à jour')
                            ->success()
                            ->send();
                    }

                    $this->record->refresh();
                }),

            EditAction::make(),
        ];
    }
}
