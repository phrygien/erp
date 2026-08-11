<?php

namespace App\Filament\Resources\ReceptionCommandes\Pages;

use App\Filament\Resources\ReceptionCommandes\ReceptionCommandeResource;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReceptionCommande extends ViewRecord
{
    protected static string $resource = ReceptionCommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generer_pdf')
                ->label('Générer le PDF')
                ->color('gray')
                ->action(function () {
                    $reception = $this->record->load([
                        'commande.fournisseur',
                        'commande.magasin',
                        'bonCommande',
                        'receivedBy',
                        'details.product',
                        'details.detailCommande',
                    ]);

                    $pdf = Pdf::loadView('pdf.reception-commande', [
                        'reception' => $reception,
                        'formattedDate' => $reception->date_reception?->translatedFormat('j F Y'),
                        'fournisseur' => $reception->commande?->fournisseur,
                        'magasin' => $reception->commande?->magasin,
                    ]);

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'bon-reception-' . $reception->numero_reception . '.pdf',
                    );
                }),

            EditAction::make(),

            DeleteAction::make(),
        ];
    }
}
