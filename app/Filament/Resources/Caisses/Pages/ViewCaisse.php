<?php

namespace App\Filament\Resources\Caisses\Pages;

use App\Filament\Resources\Caisses\CaisseResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCaisse extends ViewRecord
{
    protected static string $resource = CaisseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
