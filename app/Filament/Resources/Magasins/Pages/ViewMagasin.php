<?php

namespace App\Filament\Resources\Magasins\Pages;

use App\Filament\Resources\Magasins\MagasinResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMagasin extends ViewRecord
{
    protected static string $resource = MagasinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
