<?php

namespace App\Filament\Resources\Fournisseurs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FournisseurForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Informations générales')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required(),

                        TextInput::make('code')
                            ->required(),

                        Textarea::make('raison_social')
                            ->label('Raison sociale'),

                        TextInput::make('telephone')
                            ->tel()
                            ->required(),

                        TextInput::make('fax'),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),

                        Toggle::make('state')
                            ->label('Actif')
                            ->default(true)
                            ->formatStateUsing(fn ($state) => $state === 'active')
                            ->dehydrateStateUsing(fn ($state) => $state ? 'active' : 'inactive'),
                    ]),

                Section::make('Adresses')
                    ->columnSpan(1)
                    ->schema([
                        Textarea::make('adresse_siege')
                            ->label('Adresse siège')
                            ->required(),

                        TextInput::make('code_postal')
                            ->label('Code postal')
                            ->required(),

                        TextInput::make('ville')
                            ->label('Ville')
                            ->required(),

                        TextInput::make('adresse_retour')
                            ->label('Adresse de retour'),

                        TextInput::make('code_postal_retour')
                            ->label('Code postal (retour)'),

                        TextInput::make('ville_retour')
                            ->label('Ville (retour)'),
                    ]),
            ]);
    }
}
