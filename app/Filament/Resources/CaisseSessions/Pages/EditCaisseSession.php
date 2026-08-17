<?php

namespace App\Filament\Resources\CaisseSessions\Pages;

use App\Filament\Resources\CaisseSessions\CaisseSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCaisseSession extends EditRecord
{
    protected static string $resource = CaisseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
