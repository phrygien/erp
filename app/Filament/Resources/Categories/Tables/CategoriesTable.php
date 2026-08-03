<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('marque.name')
                    ->searchable(),
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
                            ->body($record->name . ' est maintenant ' . ($state ? 'actif' : 'inactif'))
                            ->success()
                            ->send();
                    }),
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
