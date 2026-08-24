<?php

namespace App\Filament\Resources\Ventes\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    protected static ?string $title = 'Détails de la vente';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('product.designation')
                    ->label('Produit')
                    ->searchable(),
                TextColumn::make('product.EAN')
                    ->label('EAN')
                    ->searchable(),
                TextColumn::make('stockLot.id')
                    ->label('Lot de stock')
                    ->placeholder('-'),
                TextColumn::make('quantite')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('prix_unitaire')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('montant_total_ligne')
                    ->label('Total ligne')
                    ->numeric()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('This page'),
                        Sum::make()
                            ->label('All lines')
                            ->query(fn (Builder $query) => $query),
                    ]),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}
