<?php

namespace App\Filament\Resources\Caisses\Pages;

use App\Filament\Resources\Caisses\CaisseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCaisses extends ListRecords
{
    protected static string $resource = CaisseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
