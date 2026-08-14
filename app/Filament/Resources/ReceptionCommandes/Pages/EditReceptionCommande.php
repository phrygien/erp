<?php

namespace App\Filament\Resources\ReceptionCommandes\Pages;

use App\Filament\Resources\ReceptionCommandes\ReceptionCommandeResource;
use App\Models\DetailReceptionCommande;
use App\Models\Stock;
use App\Models\StockLot;
use App\Models\StockMouvement;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Throwable;

class EditReceptionCommande extends EditRecord
{
    protected static string $resource = ReceptionCommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),

            Action::make('marquerComplete')
                ->label('Marquer comme complétée')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirmer la réception complète')
                ->modalDescription('Cette action va créer les lots de stock et créditer le stock du dépôt central pour chaque produit réceptionné. Elle ne peut pas être annulée automatiquement une fois confirmée.')
                ->visible(fn (): bool => ! in_array($this->record->statut, ['complete', 'annulee']))
                ->action(fn () => $this->marquerReceptionComplete()),

            DeleteAction::make(),
        ];
    }

    private function marquerReceptionComplete(): void
    {
        try {
            DB::transaction(function () {
                $reception = $this->record;

                $reception->details->each(function (DetailReceptionCommande $detail) use ($reception) {
                    // Ce qui rejoint réellement le stock : la quantité reçue,
                    // moins ce qui a été déclaré invendable à la réception.
                    $quantiteUtilisable = max(0, $detail->qte_recue - $detail->qte_invendable);

                    if ($quantiteUtilisable <= 0) {
                        return;
                    }

                    // updateOrCreate sur detail_reception_commande_id (unique
                    // en base) : idempotent si cette méthode est rejouée pour
                    // la même ligne de réception plutôt que de dupliquer le
                    // lot.
                    $lot = StockLot::updateOrCreate(
                        ['detail_reception_commande_id' => $detail->id],
                        [
                            'product_id' => $detail->product_id,
                            'reception_commande_id' => $reception->id,
                            'quantite_initiale' => $quantiteUtilisable,
                            'quantite_restante' => $quantiteUtilisable,
                            // Coût figé sur le lot au moment de la réception,
                            // récupéré depuis la ligne de commande d'origine.
                            'pu_achat_net' => $detail->detailCommande?->pu_achat_net ?? 0,
                            'date_entree' => $reception->date_reception,
                            'statut' => 'actif',
                        ]
                    );

                    $stock = Stock::firstOrCreate(
                        ['product_id' => $detail->product_id],
                        ['quantite' => 0]
                    );

                    StockMouvement::enregistrerEntree(
                        stock: $stock,
                        quantite: $quantiteUtilisable,
                        stockLot: $lot,
                        receptionCommandeId: $reception->id,
                        detailReceptionCommandeId: $detail->id,
                        dateMouvement: $reception->date_reception,
                        commentaire: "Réception {$reception->numero_reception}",
                    );
                });

                $reception->update(['statut' => 'complete']);
            });
        } catch (Throwable $e) {
            Notification::make()
                ->title('Échec de la mise à jour du stock')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Réception marquée comme complète')
            ->body('Le stock du dépôt central a été mis à jour.')
            ->success()
            ->send();

        $this->fillForm();
    }
}
