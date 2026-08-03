<?php

namespace App\Filament\Resources\Commandes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommandesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_commande')
                    ->label('N° Commande')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Numéro copié'),

                TextColumn::make('fournisseur.name')
                    ->label('Fournisseur')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('magasin.name')
                    ->label('Magasin')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('montant_total')
                    ->label('Montant total')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' MUR')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('etat_commande')
                    ->label('État')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pre_commande' => 'Pré-commande',
                        'commande' => 'Commande',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'pre_commande' => 'warning',
                        'commande' => 'info',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('statut_commande')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'annule' => 'Annulé',
                        'cree' => 'Créé',
                        'facturee' => 'Facturée',
                        'cloturee' => 'Clôturée',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'annule' => 'danger',
                        'cree' => 'gray',
                        'facturee' => 'info',
                        'cloturee' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('montant_minimum')
                    ->label('Montant min.')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('remise_facture')
                    ->label('Remise')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('createdBy.name')
                    ->label('Créé par')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Modifiée le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('fournisseur_id')
                    ->label('Fournisseur')
                    ->relationship('fournisseur', 'name')
                    ->searchable(),

                SelectFilter::make('magasin_id')
                    ->label('Magasin')
                    ->relationship('magasin', 'name')
                    ->searchable(),

                SelectFilter::make('etat_commande')
                    ->label('État')
                    ->options([
                        'pre_commande' => 'Pré-commande',
                        'commande' => 'Commande',
                    ]),

                SelectFilter::make('statut_commande')
                    ->label('Statut')
                    ->options([
                        'annule' => 'Annulé',
                        'cree' => 'Créé',
                        'facturee' => 'Facturée',
                        'cloturee' => 'Clôturée',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
