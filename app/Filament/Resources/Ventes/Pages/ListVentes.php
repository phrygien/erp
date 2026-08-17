<?php

namespace App\Filament\Resources\Ventes\Pages;

use App\Filament\Resources\Ventes\VenteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVentes extends ListRecords
{
    protected static string $resource = VenteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
