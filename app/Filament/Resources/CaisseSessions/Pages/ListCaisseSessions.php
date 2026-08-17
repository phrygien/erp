<?php

namespace App\Filament\Resources\CaisseSessions\Pages;

use App\Filament\Resources\CaisseSessions\CaisseSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCaisseSessions extends ListRecords
{
    protected static string $resource = CaisseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
