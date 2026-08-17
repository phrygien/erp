<?php

namespace App\Filament\Resources\CaisseSessions\Pages;

use App\Filament\Resources\CaisseSessions\CaisseSessionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCaisseSession extends ViewRecord
{
    protected static string $resource = CaisseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //EditAction::make(),
        ];
    }
}
