<?php

namespace App\Filament\Resources\Magasins\Pages;

use App\Filament\Resources\Magasins\MagasinResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMagasin extends EditRecord
{
    protected static string $resource = MagasinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
