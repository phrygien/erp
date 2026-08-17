<?php

namespace App\Filament\Resources\Ventes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VentesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.id')
                    ->searchable(),
                TextColumn::make('magasin.name')
                    ->searchable(),
                TextColumn::make('stockLot.id')
                    ->searchable(),
                TextColumn::make('canal_vente')
                    ->searchable(),
                TextColumn::make('caisseSession.id')
                    ->searchable(),
                TextColumn::make('quantite')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('montant_total_ht_vente')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
