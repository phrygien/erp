<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('designation')
                    ->label('Désignation')
                    ->searchable()
                    ->description(fn ($record) => $record->designation_variant)
                    ->wrap(),

                TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('marque.name')
                    ->label('Marque')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type.name')
                    ->label('Type')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('ligne.name')
                    ->label('Ligne')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('EAN')
                    ->label('EAN')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('EAN copié')
                    ->toggleable(),

                TextColumn::make('pght_parkod')
                    ->label('Prix')
                    ->formatStateUsing(fn ($state, $record) => number_format($state, 2) . ' ' . $record->devise)
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('tva')
                    ->label('TVA')
                    ->formatStateUsing(fn ($state) => $state . ' %')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

                ToggleColumn::make('state')
                    ->label('Actif')
                    ->getStateUsing(fn ($record) => $record->state === 'active')
                    ->updateStateUsing(function ($record, $state) {
                        $record->state = $state ? 'active' : 'inactive';
                        $record->save();
                    })
                    ->afterStateUpdated(function ($record, $state) {
                        Notification::make()
                            ->title('Statut mis à jour')
                            ->body($record->designation . ' est maintenant ' . ($state ? 'actif' : 'inactif'))
                            ->success()
                            ->send();
                    }),

                TextColumn::make('hs_code')
                    ->label('HS Code')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('article')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ref_fabri_n_1')
                    ->label('Réf. fabricant')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('devise')
                    ->label('Devise')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                SelectFilter::make('category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),

                SelectFilter::make('marque_id')
                    ->label('Marque')
                    ->relationship('marque', 'name'),

                SelectFilter::make('type_id')
                    ->label('Type')
                    ->relationship('type', 'name'),

                SelectFilter::make('ligne_id')
                    ->label('Ligne')
                    ->relationship('ligne', 'name'),

                TernaryFilter::make('state')
                    ->label('Actif')
                    ->placeholder('Tous')
                    ->trueLabel('Actifs')
                    ->falseLabel('Inactifs')
                    ->queries(
                        true: fn ($query) => $query->where('state', 'active'),
                        false: fn ($query) => $query->where('state', 'inactive'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('designation');
    }
}
