<?php

namespace App\Filament\Resources\BonCommandes\Pages;

use App\Filament\Resources\BonCommandes\BonCommandeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBonCommande extends ViewRecord
{
    protected static string $resource = BonCommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //EditAction::make(),
        ];
    }
}
