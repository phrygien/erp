<?php

namespace App\Filament\Resources\Ventes\Tables;

use App\Filament\Exports\VenteExporter;
use App\Models\Caisse;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VentesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.designation')
                    ->label('Produit')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.EAN')
                    ->label('EAN')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('magasin.name')
                    ->searchable(),
                TextColumn::make('canal_vente')
                    ->searchable(),
                TextColumn::make('caisseSession.caisse.name')
                    ->label('Caisse')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('quantite')
                    ->numeric()
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Total')
                    ),
                TextColumn::make('montant_total_ht_vente')
                    ->numeric()
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Total HT')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' EUR')
                    ),
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
                SelectFilter::make('magasin_id')
                    ->label('Magasin')
                    ->relationship('magasin', 'name')
                    ->searchable()
                    ->preload(),

                // Pas de relation directe caisse sur Vente (elle passe par
                // caisseSession), donc filtre manuel via whereHas plutôt
                // que SelectFilter::relationship().
                SelectFilter::make('caisse_id')
                    ->label('Caisse')
                    ->options(fn () => Caisse::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'],
                            fn (Builder $q, $caisseId) => $q->whereHas(
                                'caisseSession',
                                fn (Builder $sq) => $sq->where('caisse_id', $caisseId)
                            )
                        );
                    }),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exporter')
                    ->exporter(VenteExporter::class)
                    ->formats([
                        ExportFormat::Xlsx,
                        ExportFormat::Csv,
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->label('Exporter la sélection')
                        ->exporter(VenteExporter::class),
                ]),
            ]);
    }
}
