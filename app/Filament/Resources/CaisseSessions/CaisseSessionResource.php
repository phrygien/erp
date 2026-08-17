<?php

namespace App\Filament\Resources\CaisseSessions;

use App\Filament\Resources\CaisseSessions\Pages\CreateCaisseSession;
use App\Filament\Resources\CaisseSessions\Pages\EditCaisseSession;
use App\Filament\Resources\CaisseSessions\Pages\ListCaisseSessions;
use App\Filament\Resources\CaisseSessions\Pages\ViewCaisseSession;
use App\Filament\Resources\CaisseSessions\Schemas\CaisseSessionForm;
use App\Filament\Resources\CaisseSessions\Schemas\CaisseSessionInfolist;
use App\Filament\Resources\CaisseSessions\Tables\CaisseSessionsTable;
use App\Models\CaisseSession;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CaisseSessionResource extends Resource
{
    protected static string | UnitEnum | null $navigationGroup = 'POS';

    protected static ?string $model = CaisseSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CaisseSessionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CaisseSessionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CaisseSessionsTable::configure($table);
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
            'index' => ListCaisseSessions::route('/'),
            'create' => CreateCaisseSession::route('/create'),
            'view' => ViewCaisseSession::route('/{record}'),
            'edit' => EditCaisseSession::route('/{record}/edit'),
        ];
    }
}
