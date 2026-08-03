<?php

namespace App\Filament\Resources\Magasins;

use App\Filament\Resources\Magasins\Pages\CreateMagasin;
use App\Filament\Resources\Magasins\Pages\EditMagasin;
use App\Filament\Resources\Magasins\Pages\ListMagasins;
use App\Filament\Resources\Magasins\Pages\ViewMagasin;
use App\Filament\Resources\Magasins\Schemas\MagasinForm;
use App\Filament\Resources\Magasins\Schemas\MagasinInfolist;
use App\Filament\Resources\Magasins\Tables\MagasinsTable;
use App\Models\Magasin;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MagasinResource extends Resource
{
    protected static string | UnitEnum | null $navigationGroup = 'Depot et Magasins';

    protected static ?string $model = Magasin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return MagasinForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MagasinInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MagasinsTable::configure($table);
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
            'index' => ListMagasins::route('/'),
            'create' => CreateMagasin::route('/create'),
            'view' => ViewMagasin::route('/{record}'),
            'edit' => EditMagasin::route('/{record}/edit'),
        ];
    }
}
