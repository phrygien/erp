<?php

namespace App\Filament\Resources\Factures\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StatusHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistories';

    protected static ?string $title = 'Historique des statuts';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('ancienne_valeur')
                    ->label('Ancien statut')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-'),

                TextColumn::make('nouvelle_valeur')
                    ->label('Nouveau statut')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('changedBy.name')
                    ->label('Modifié par')
                    ->placeholder('-'),

                TextColumn::make('commentaire')
                    ->limit(50)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            // Historique d'audit : on consulte, on ne modifie ni ne supprime
            // une entrée après coup. Une correction de statut doit créer une
            // nouvelle ligne d'historique, jamais éditer une existante.
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
