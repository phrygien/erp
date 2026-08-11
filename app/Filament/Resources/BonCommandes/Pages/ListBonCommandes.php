<?php

namespace App\Filament\Resources\BonCommandes\Pages;

use App\Filament\Resources\BonCommandes\BonCommandeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBonCommandes extends ListRecords
{
    protected static string $resource = BonCommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
           // CreateAction::make(),
        ];
    }
}
