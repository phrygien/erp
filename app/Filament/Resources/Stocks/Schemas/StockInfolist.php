<?php

namespace App\Filament\Resources\Stocks\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('product.product_code')
                            ->label('Code produit'),

                        TextEntry::make('product.EAN')
                            ->label('EAN')
                            ->placeholder('-'),

                        TextEntry::make('product.designation')
                            ->label('Désignation')
                            ->columnSpanFull(),

                        TextEntry::make('product.category.name')
                            ->label('Catégorie')
                            ->placeholder('-'),

                        TextEntry::make('product.marque.name')
                            ->label('Marque')
                            ->placeholder('-'),

                        TextEntry::make('product.type.name')
                            ->label('Type')
                            ->placeholder('-'),

                        TextEntry::make('quantite')
                            ->label('Quantité')
                            ->numeric()
                            ->badge()
                            ->color(fn (int $state): string => match (true) {
                                $state <= 0 => 'danger',
                                $state < 10 => 'warning',
                                default => 'success',
                            }),

                        TextEntry::make('statut_libelle')
                            ->label('Statut')
                            ->state(fn ($record): string => match (true) {
                                $record->quantite <= 0 => 'Rupture de stock',
                                $record->quantite < 10 => 'Stock faible',
                                default => 'Disponible',
                            })
                            ->badge()
                            ->color(fn ($record): string => match (true) {
                                $record->quantite <= 0 => 'danger',
                                $record->quantite < 10 => 'warning',
                                default => 'success',
                            }),

                        TextEntry::make('gen_code')
                            ->label('Code stock')
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime()
                            ->placeholder('-'),

                        TextEntry::make('updated_at')
                            ->label('Dernier mouvement')
                            ->dateTime()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
