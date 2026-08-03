<?php

namespace App\Filament\Resources\Marques\RelationManagers;

use App\Models\Category;
use App\Models\Type;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $recordTitleAttribute = 'designation';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('designation')
            ->columns([
                TextColumn::make('product_code')
                    ->label('Code'),
                TextColumn::make('designation')
                    ->label('Désignation')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Catégorie'),
                TextColumn::make('type.name')
                    ->label('Type'),
                TextColumn::make('pght_parkod')
                    ->label('Prix')
                    ->formatStateUsing(fn ($state, $record) => number_format($state, 2) . ' ' . $record->devise),
                TextColumn::make('state')
                    ->label('Statut')
                    ->badge()
                    ->color(fn ($state) => $state === 'active' ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),

                SelectFilter::make('type_id')
                    ->label('Type')
                    ->relationship('type', 'name'),
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
