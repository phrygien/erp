<?php

namespace App\Filament\Resources\CaisseSessions\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VentesRelationManager extends RelationManager
{
    protected static string $relationship = 'ventes';

    protected static ?string $title = 'Ventes de la session';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero_vente')
            ->columns([
                TextColumn::make('numero_vente')
                    ->label('N° de vente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('details_count')
                    ->label('Nb articles')
                    ->counts('details')
                    ->numeric()
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('montant_total')
                    ->label('Total')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' EUR')
                    ->sortable()
                    ->weight('semibold')
                    ->summarize(
                        Sum::make()
                            ->label('Total')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' EUR')
                    ),

                TextColumn::make('created_at')
                    ->label('Heure')
                    ->dateTime('H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            // Lecture seule : une session (surtout fermée) n'est pas censée
            // être modifiée après coup, cohérent avec le comportement déjà
            // vu dans CaisseSessionsTable (EditAction masquée si fermée).
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([])
            ->emptyStateHeading('Aucune vente enregistrée')
            ->emptyStateDescription('Les ventes de cette session apparaîtront ici au fur et à mesure.')
            ->emptyStateIcon('heroicon-o-shopping-cart')
            ->paginated([10, 25, 50]);
    }
}
