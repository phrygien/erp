<?php

namespace App\Filament\Resources\BonCommandes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BonCommandeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('numero')
                    ->required(),
                Select::make('commande_id')
                    ->relationship('commande', 'id')
                    ->required(),
                TextInput::make('code_fournisseur')
                    ->required(),
                TextInput::make('numero_compte'),
                DatePicker::make('date_commande'),
                DatePicker::make('date_livraison'),
                Select::make('magasin_facturation_id')
                    ->relationship('magasinFacturation', 'name')
                    ->required(),
                Select::make('magasin_livraison_id')
                    ->relationship('magasinLivraison', 'name')
                    ->required(),
                TextInput::make('montant_commande')
                    ->required()
                    ->numeric(),
                TextInput::make('statut_commande')
                    ->required()
                    ->default('cree'),
                TextInput::make('created_by')
                    ->numeric(),
            ]);
    }
}
