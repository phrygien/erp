<?php

namespace App\Filament\Resources\StockMouvements;

use App\Filament\Resources\StockMouvements\Pages\CreateStockMouvement;
use App\Filament\Resources\StockMouvements\Pages\EditStockMouvement;
use App\Filament\Resources\StockMouvements\Pages\ListStockMouvements;
use App\Filament\Resources\StockMouvements\Schemas\StockMouvementForm;
use App\Filament\Resources\StockMouvements\Tables\StockMouvementsTable;
use App\Models\StockMouvement;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockMouvementResource extends Resource
{
    protected static string | UnitEnum | null $navigationGroup = 'Approvisionements';

    protected static ?string $model = StockMouvement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return StockMouvementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockMouvementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockMouvements::route('/'),
            'create' => CreateStockMouvement::route('/create'),
            'edit' => EditStockMouvement::route('/{record}/edit'),
        ];
    }
}
