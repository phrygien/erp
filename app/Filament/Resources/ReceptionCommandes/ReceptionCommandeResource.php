<?php

namespace App\Filament\Resources\ReceptionCommandes;

use App\Filament\Resources\ReceptionCommandes\Pages\CreateReceptionCommande;
use App\Filament\Resources\ReceptionCommandes\Pages\EditReceptionCommande;
use App\Filament\Resources\ReceptionCommandes\Pages\ListReceptionCommandes;
use App\Filament\Resources\ReceptionCommandes\Pages\ViewReceptionCommande;
use App\Filament\Resources\ReceptionCommandes\Schemas\ReceptionCommandeForm;
use App\Filament\Resources\ReceptionCommandes\Schemas\ReceptionCommandeInfolist;
use App\Filament\Resources\ReceptionCommandes\Tables\ReceptionCommandesTable;
use App\Models\ReceptionCommande;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReceptionCommandeResource extends Resource
{
    protected static string | UnitEnum | null $navigationGroup = 'Commandes';

    protected static ?string $model = ReceptionCommande::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $recordTitleAttribute = 'numero_reception';

    public static function form(Schema $schema): Schema
    {
        return ReceptionCommandeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReceptionCommandeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReceptionCommandesTable::configure($table);
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
            'index' => ListReceptionCommandes::route('/'),
            'create' => CreateReceptionCommande::route('/create'),
            'view' => ViewReceptionCommande::route('/{record}'),
            'edit' => EditReceptionCommande::route('/{record}/edit'),
        ];
    }
}
