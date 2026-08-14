<?php

namespace App\Filament\Resources\Stocks\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MouvementsRelationManager extends RelationManager
{
    protected static string $relationship = 'mouvements';

    protected static ?string $title = 'Mouvements de stock';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('date_mouvement')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'entree' => 'success',
                        'sortie' => 'danger',
                        'ajustement' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'entree' => 'Entrée',
                        'sortie' => 'Sortie',
                        'ajustement' => 'Ajustement',
                        default => $state,
                    }),

                TextColumn::make('quantite')
                    ->label('Quantité')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('quantite_avant')
                    ->label('Avant')
                    ->numeric()
                    ->toggleable(),

                TextColumn::make('quantite_apres')
                    ->label('Après')
                    ->numeric()
                    ->toggleable(),

                TextColumn::make('receptionCommande.numero_reception')
                    ->label('N° réception')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('stockLot.id')
                    ->label('Lot')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('commentaire')
                    ->limit(40)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('createdBy.name')
                    ->label('Par')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Enregistré le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date_mouvement', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'entree' => 'Entrée',
                        'sortie' => 'Sortie',
                        'ajustement' => 'Ajustement',
                    ]),
            ])
            // Historique d'audit : on consulte, on ne modifie ni ne supprime
            // un mouvement après coup. Toute correction doit passer par un
            // nouveau mouvement d'ajustement, jamais par l'édition d'un
            // mouvement existant.
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
