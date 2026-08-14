<?php

namespace App\Filament\Resources\StockMouvements\Tables;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMouvementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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

                TextColumn::make('product.product_code')
                    ->label('Code produit')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product.EAN')
                    ->label('EAN')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('product.designation')
                    ->label('Produit')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('quantite')
                    ->label('Quantité')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('quantite_avant')
                    ->label('Avant')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('quantite_apres')
                    ->label('Après')
                    ->numeric()
                    ->sortable()
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

                SelectFilter::make('product_id')
                    ->label('Produit')
                    ->relationship('product', 'designation')
                    ->searchable()
                    ->preload(),

                Filter::make('date_mouvement')
                    ->schema([
                        DatePicker::make('du')
                            ->label('Du'),
                        DatePicker::make('au')
                            ->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['du'] ?? null, fn (Builder $q, $date) => $q->whereDate('date_mouvement', '>=', $date))
                            ->when($data['au'] ?? null, fn (Builder $q, $date) => $q->whereDate('date_mouvement', '<=', $date));
                    }),
            ])
            // Historique d'audit : lecture seule. Aucune correction ne doit
            // passer par l'édition d'un mouvement existant, seulement par la
            // création d'un nouveau mouvement (ajustement).
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
