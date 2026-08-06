<?php

namespace App\Filament\Resources\ReceptionCommandes\Pages;

use App\Filament\Resources\ReceptionCommandes\ReceptionCommandeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReceptionCommandes extends ListRecords
{
    protected static string $resource = ReceptionCommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
