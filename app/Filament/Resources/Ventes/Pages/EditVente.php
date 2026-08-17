<?php

namespace App\Filament\Resources\Ventes\Pages;

use App\Filament\Resources\Ventes\VenteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVente extends EditRecord
{
    protected static string $resource = VenteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
