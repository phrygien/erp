<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Select::make('marque_id')
                    ->relationship('marque', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('state')
                    ->required()
                    ->default('active'),
            ]);
    }
}
