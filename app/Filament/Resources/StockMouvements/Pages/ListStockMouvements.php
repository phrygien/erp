<?php

namespace App\Filament\Resources\StockMouvements\Pages;

use App\Filament\Resources\StockMouvements\StockMouvementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockMouvements extends ListRecords
{
    protected static string $resource = StockMouvementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //CreateAction::make(),
        ];
    }
}
