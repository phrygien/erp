<?php

namespace App\Filament\Resources\StockMouvements\Pages;

use App\Filament\Resources\StockMouvements\StockMouvementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStockMouvement extends EditRecord
{
    protected static string $resource = StockMouvementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
