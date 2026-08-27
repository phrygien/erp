<?php

namespace App\Filament\Widgets;

use App\Models\Stock;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StocksEnRuptureTable extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Stocks en rupture';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Stock::query()
                    ->with('product.type')
                    ->where('quantite', '<=', 0)
                    ->orderBy('quantite')
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.designation')
                    ->label('Désignation')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.product_code')
                    ->label('Code produit')
                    ->searchable(),

                Tables\Columns\TextColumn::make('product.EAN')
                    ->label('EAN')
                    ->searchable(),

                Tables\Columns\TextColumn::make('product.type.name')
                    ->label('Type')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.marque.name')
                    ->label('Marque')
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantite')
                    ->label('Quantité')
                    ->badge()
                    ->color(fn (int $state): string => $state < 0 ? 'danger' : 'warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Dernière mise à jour')
                    ->formatStateUsing(fn ($state) => $state?->translatedFormat('d F Y'))
                    ->sortable(),
            ])
            ->defaultSort('quantite')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Aucun stock en rupture')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
