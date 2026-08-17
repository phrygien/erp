<?php

namespace App\Filament\Resources\Caisses\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CaisseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('numero_caisse')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Select::make('magasin_id')
                    ->relationship('magasin', 'name')
                    ->required(),
                Toggle::make('active')
                    ->required(),
            ]);
    }
}
