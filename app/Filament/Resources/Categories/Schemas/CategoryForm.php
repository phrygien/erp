<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Informations générales')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('code')
                            ->required(),

                        TextInput::make('name')
                            ->required(),

                        Select::make('marque_id')
                            ->label('Marque')
                            ->relationship('marque', 'name')
                            ->searchable()
                            ->required(),
                    ]),

                Section::make('Statut')
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('state')
                            ->label('Actif')
                            ->default(true)
                            ->formatStateUsing(fn ($state) => $state === 'active')
                            ->dehydrateStateUsing(fn ($state) => $state ? 'active' : 'inactive'),
                    ]),
            ]);
    }
}
