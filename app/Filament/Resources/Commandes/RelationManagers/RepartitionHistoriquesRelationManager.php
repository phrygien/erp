<?php

namespace App\Filament\Resources\Commandes\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RepartitionHistoriquesRelationManager extends RelationManager
{
    protected static string $relationship = 'repartitionHistoriques';

    protected static ?string $title = 'Historique des répartitions';

    public function form(Schema $schema): Schema
    {
        // Lecture seule : pas de création/édition depuis cet historique.
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('champ')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('repartition.detailCommande.product.designation')
                    ->label('Produit')
                    ->searchable(),

                TextColumn::make('repartition.magasin.name')
                    ->label('Magasin'),

                TextColumn::make('ancienne_valeur')
                    ->label('Ancienne quantité')
                    ->placeholder('—'),

                TextColumn::make('nouvelle_valeur')
                    ->label('Nouvelle quantité'),

                TextColumn::make('changedBy.name')
                    ->label('Modifié par')
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
