<?php

namespace App\Filament\Resources\Ventes\Tables;

use App\Filament\Exports\VenteExporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;

class VentesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_vente')
                    ->searchable(),
                TextColumn::make('magasin.name')
                    ->searchable(),
                TextColumn::make('caisseSession.caisse.numero_caisse')
                    ->label('Caisse')
                    ->searchable(),
                TextColumn::make('details_count')
                    ->label('Nb articles')
                    ->counts('details')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date de vente')
                    ->formatStateUsing(fn ($state) => $state?->locale('fr')->isoFormat('D MMMM, YYYY'))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('montant_total')
                    ->numeric()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('This page'),
                        Sum::make()
                            ->label('All orders')
                            ->query(fn (Builder $query) => $query),
                    ]),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(VenteExporter::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(VenteExporter::class),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
