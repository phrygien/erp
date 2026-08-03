<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                // Colonne principale (2/3) - tout empilé ensemble
                Group::make([
                    Section::make('Informations générales')
                        ->columns(2)
                        ->schema([
                            TextInput::make('designation')
                                ->label('Désignation')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('product_code')
                                ->label('Code produit')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('designation_variant')
                                ->label('Variante')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            TextInput::make('article')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('ref_fabri_n_1')
                                ->label('Réf. fabricant')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('EAN')
                                ->label('EAN')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),

                            TextInput::make('hs_code')
                                ->label('HS Code')
                                ->required()
                                ->maxLength(255),
                        ]),

                    Section::make('Tarification')
                        ->columns(3)
                        ->schema([
                            TextInput::make('pght_parkod')
                                ->label('Prix')
                                ->required()
                                ->numeric()
                                ->prefix('EUR'),

                            TextInput::make('tva')
                                ->label('TVA')
                                ->required()
                                ->numeric()
                                ->integer()
                                ->suffix('%'),

                            TextInput::make('devise')
                                ->label('Devise')
                                ->required()
                                ->default('EUR')
                                ->maxLength(255),
                        ]),
                ])
                    ->columnSpan(2),

                // Sidebar (1/3) - tout empilé ensemble
                Group::make([
                    Section::make('Statut')
                        ->schema([
                            Toggle::make('state')
                                ->label('Actif')
                                ->default(true)
                                ->formatStateUsing(fn ($state) => $state === 'active')
                                ->dehydrateStateUsing(fn ($state) => $state ? 'active' : 'inactive')
                                ->helperText('Ce produit sera masqué de tous les canaux de vente.'),

                            TextInput::make('statut_parkod')
                                ->label('Statut Parkod')
                                ->required()
                                ->maxLength(255),
                        ]),

                    Section::make('Associations')
                        ->schema([
                            Select::make('marque_id')
                                ->label('Marque')
                                ->relationship('marque', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('category_id')
                                ->label('Catégorie')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('type_id')
                                ->label('Type')
                                ->relationship('type', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('ligne_id')
                                ->label('Ligne')
                                ->relationship('ligne', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ]),
                ])
                    ->columnSpan(1),
            ]);
    }
}
