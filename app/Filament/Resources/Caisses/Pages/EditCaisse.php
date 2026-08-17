<?php

namespace App\Filament\Resources\Caisses\Pages;

use App\Filament\Resources\Caisses\CaisseResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCaisse extends EditRecord
{
    protected static string $resource = CaisseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
