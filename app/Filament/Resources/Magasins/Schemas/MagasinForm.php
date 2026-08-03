<?php

namespace App\Filament\Resources\Magasins\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MagasinForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required(),

                        Select::make('type')
                            ->options([
                                'en ligne' => 'En ligne',
                                'physique' => 'Physique',
                            ])
                            ->required()
                            ->default('en ligne'),

                        Textarea::make('adresse')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('telephone')
                            ->tel()
                            ->required(),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),

                        Toggle::make('active')
                            ->label('Actif')
                            ->required(),
                    ]),
            ]);
    }
}
