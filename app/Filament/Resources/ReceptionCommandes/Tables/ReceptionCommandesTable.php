<?php

namespace App\Filament\Resources\ReceptionCommandes\Tables;

use App\Models\ReceptionCommande;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ReceptionCommandesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_reception')
                    ->label('N° réception')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),

                TextColumn::make('commande.numero_commande')
                    ->label('Commande')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('bonCommande.numero')
                    ->label('Bon de commande')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('numero_bl')
                    ->label('N° BL fournisseur')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('date_reception')
                    ->label('Date réception')
                    ->formatStateUsing(fn ($state) => $state?->locale('fr')->translatedFormat('d F Y'))
                    ->sortable(),

                TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ReceptionCommande::STATUT_EN_COURS => 'En cours',
                        ReceptionCommande::STATUT_PARTIELLE => 'Partielle',
                        ReceptionCommande::STATUT_COMPLETE => 'Complète',
                        ReceptionCommande::STATUT_ANNULEE => 'Annulée',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        ReceptionCommande::STATUT_EN_COURS => 'warning',
                        ReceptionCommande::STATUT_PARTIELLE => 'info',
                        ReceptionCommande::STATUT_COMPLETE => 'success',
                        ReceptionCommande::STATUT_ANNULEE => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('receivedBy.name')
                    ->label('Réceptionné par')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date_reception', 'desc')
            ->filters([
                SelectFilter::make('statut')
                    ->label('Statut')
                    ->options([
                        ReceptionCommande::STATUT_EN_COURS => 'En cours',
                        ReceptionCommande::STATUT_PARTIELLE => 'Partielle',
                        ReceptionCommande::STATUT_COMPLETE => 'Complète',
                        ReceptionCommande::STATUT_ANNULEE => 'Annulée',
                    ]),

                SelectFilter::make('commande_id')
                    ->label('Commande')
                    ->relationship('commande', 'numero_commande')
                    ->searchable()
                    ->preload(),

                Filter::make('date_reception')
                    ->schema([
                        DatePicker::make('date_de')
                            ->label('Du'),
                        DatePicker::make('date_a')
                            ->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_de'],
                                fn (Builder $q, $date) => $q->whereDate('date_reception', '>=', $date),
                            )
                            ->when(
                                $data['date_a'],
                                fn (Builder $q, $date) => $q->whereDate('date_reception', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['date_de'] ?? null) {
                            $indicators[] = 'Du ' . Carbon::parse($data['date_de'])->format('d/m/Y');
                        }

                        if ($data['date_a'] ?? null) {
                            $indicators[] = 'Au ' . Carbon::parse($data['date_a'])->format('d/m/Y');
                        }

                        return $indicators;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
