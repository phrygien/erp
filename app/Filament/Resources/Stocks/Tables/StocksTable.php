<?php

namespace App\Filament\Resources\Stocks\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use App\Filament\Exports\StockExporter;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Models\Stock;
use App\Models\StockMouvement;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class StocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.product_code')
                    ->label('Code produit')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product.EAN')
                    ->label('EAN')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('product.designation')
                    ->label('Désignation')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('product.category.name')
                    ->label('Catégorie')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('product.marque.name')
                    ->label('Marque')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('product.type.name')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('quantite')
                    ->label('Quantité')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    }),

                TextColumn::make('gen_code')
                    ->label('Code stock')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Dernier mouvement')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('quantite')
            ->filters([
                SelectFilter::make('product.category_id')
                    ->label('Catégorie')
                    ->relationship('product.category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('product.marque_id')
                    ->label('Marque')
                    ->relationship('product.marque', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('product.type_id')
                    ->label('Type')
                    ->relationship('product.type', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('rupture')
                    ->label('En rupture de stock')
                    ->query(fn (Builder $query): Builder => $query->where('quantite', '<=', 0)),

                Filter::make('stock_faible')
                    ->label('Stock faible (< 10)')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('quantite', [1, 9])),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('ajuster')
                    ->label('Ajuster')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->color('warning')
                    ->schema([
                        TextInput::make('nouvelle_quantite')
                            ->label('Nouvelle quantité')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->helperText(fn (Stock $record): string => "Quantité actuelle : {$record->quantite}"),

                        Textarea::make('motif')
                            ->label('Motif de l\'ajustement')
                            ->placeholder('Ex : inventaire physique, casse constatée, erreur de saisie...')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->action(function (Stock $record, array $data) {
                        // Rien à faire si la quantité saisie est identique à
                        // l'actuelle : évite de créer un mouvement vide dans
                        // l'historique.
                        if ((int) $data['nouvelle_quantite'] === $record->quantite) {
                            Notification::make()
                                ->title('Aucun changement')
                                ->body('La quantité saisie est identique à la quantité actuelle.')
                                ->warning()
                                ->send();

                            return;
                        }

                        try {
                            StockMouvement::enregistrerAjustement(
                                stock: $record,
                                nouvelleQuantite: (int) $data['nouvelle_quantite'],
                                commentaire: $data['motif'],
                            );
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('Échec de l\'ajustement')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Stock ajusté')
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exporter')
                    ->exporter(StockExporter::class),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->label('Exporter la sélection')
                        ->exporter(StockExporter::class),
                ]),
            ]);
    }
}
