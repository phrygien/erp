<?php

namespace App\Filament\Resources\Ventes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class VenteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('montant_total')
                    ->required()
                    ->numeric(),
                Select::make('magasin_id')
                    ->relationship('magasin', 'name')
                    ->required(),
                Textarea::make('canal_vente')
                    ->required()
                    ->default('caisse')
                    ->columnSpanFull(),
                Select::make('caisse_session_id')
                    ->relationship('caisseSession', 'id'),
                Textarea::make('numero_vente')
                    ->columnSpanFull(),
            ]);
    }
}
