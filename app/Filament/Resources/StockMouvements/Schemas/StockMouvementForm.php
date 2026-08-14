<?php

namespace App\Filament\Resources\StockMouvements\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class StockMouvementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'id')
                    ->required(),
                Select::make('stock_lot_id')
                    ->relationship('stockLot', 'id'),
                TextInput::make('type')
                    ->required()
                    ->default('entree'),
                TextInput::make('quantite')
                    ->required()
                    ->numeric(),
                TextInput::make('quantite_avant')
                    ->required()
                    ->numeric(),
                TextInput::make('quantite_apres')
                    ->required()
                    ->numeric(),
                DatePicker::make('date_mouvement')
                    ->required(),
                Select::make('reception_commande_id')
                    ->relationship('receptionCommande', 'id'),
                Select::make('detail_reception_commande_id')
                    ->relationship('detailReceptionCommande', 'id'),
                Textarea::make('commentaire')
                    ->columnSpanFull(),
                TextInput::make('created_by')
                    ->numeric(),
            ]);
    }
}
