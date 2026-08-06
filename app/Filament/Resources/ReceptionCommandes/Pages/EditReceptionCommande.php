<?php

namespace App\Filament\Resources\ReceptionCommandes\Pages;

use App\Filament\Resources\ReceptionCommandes\ReceptionCommandeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditReceptionCommande extends EditRecord
{
    protected static string $resource = ReceptionCommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
