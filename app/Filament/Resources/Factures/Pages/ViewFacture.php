<?php

namespace App\Filament\Resources\Factures\Pages;

use App\Filament\Resources\Factures\FactureResource;
use App\Models\Facture;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFacture extends ViewRecord
{
    protected static string $resource = FactureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
            ->visible(fn (Facture $record): bool => $record->statut !== 'paye'),
        ];
    }
}
