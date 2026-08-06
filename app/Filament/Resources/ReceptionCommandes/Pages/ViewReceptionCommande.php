<?php

namespace App\Filament\Resources\ReceptionCommandes\Pages;

use App\Filament\Resources\ReceptionCommandes\ReceptionCommandeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewReceptionCommande extends ViewRecord
{
    protected static string $resource = ReceptionCommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
