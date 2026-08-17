<?php

namespace App\Filament\Resources\CaisseSessions\Tables;

use App\Models\CaisseSession;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class CaisseSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('caisse.name')
                    ->label('Caisse')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date_session')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('ouverte_le')
                    ->label('Ouverte le')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('fermee_le')
                    ->label('Fermée le')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('solde_ouverture')
                    ->label('Solde ouverture')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' EUR')
                    ->sortable(),

                TextColumn::make('solde_cloture_theorique')
                    ->label('Théorique')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' EUR')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('solde_cloture_reel')
                    ->label('Réel')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' EUR')
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('ecart')
                    ->label('Écart')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' EUR')
                    ->sortable()
                    ->placeholder('-')
                    ->badge()
                    ->color(fn (?string $state): string => match (true) {
                        $state === null => 'gray',
                        (float) $state === 0.0 => 'success',
                        default => 'danger',
                    }),

                TextColumn::make('statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ouverte' => 'success',
                        'fermee' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ouverte' => 'Ouverte',
                        'fermee' => 'Fermée',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('ouverte_le', 'desc')
            ->filters([
                SelectFilter::make('statut')
                    ->options([
                        'ouverte' => 'Ouverte',
                        'fermee' => 'Fermée',
                    ]),

                SelectFilter::make('caisse_id')
                    ->label('Caisse')
                    ->relationship('caisse', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('responsable_id')
                    ->label('Responsable')
                    ->relationship('responsable', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('avec_ecart')
                    ->label('Avec écart de caisse')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('ecart')
                        ->where('ecart', '!=', 0)),
            ])
            ->recordActions([
                ViewAction::make(),

                // Une session fermée est un enregistrement figé, au même
                // titre qu'une facture payée : la rouvrir en édition
                // fausserait un rapprochement déjà effectué.
                EditAction::make()
                    ->visible(fn (CaisseSession $record): bool => $record->estOuverte()),

                Action::make('fermer')
                    ->label('Fermer la session')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->color('danger')
                    ->visible(fn (CaisseSession $record): bool => $record->estOuverte())
                    ->requiresConfirmation()
                    ->modalHeading('Clôturer la session de caisse')
                    ->modalDescription('Cette action fige la session : elle ne pourra plus être modifiée ensuite.')
                    ->schema([
                        // Pas de calcul automatique du solde théorique tant
                        // que caisse_mouvements n'existe pas : il faut le
                        // saisir manuellement pour l'instant. Une fois cette
                        // table en place, ce champ pourra être pré-rempli et
                        // rendu readOnly plutôt que saisi à la main.
                        TextInput::make('solde_cloture_theorique')
                            ->label('Solde théorique')
                            ->numeric()
                            ->required()
                            ->helperText('Solde attendu selon les encaissements/décaissements de la journée.'),

                        TextInput::make('solde_cloture_reel')
                            ->label('Solde réel (comptage physique)')
                            ->numeric()
                            ->required(),

                        Textarea::make('commentaire')
                            ->label('Commentaire')
                            ->placeholder('Ex : justification de l\'écart, remarques sur la journée...')
                            ->columnSpanFull(),
                    ])
                    ->action(function (CaisseSession $record, array $data) {
                        try {
                            $record->fermer(
                                soldeTheorique: (float) $data['solde_cloture_theorique'],
                                soldeReel: (float) $data['solde_cloture_reel'],
                                commentaire: $data['commentaire'] ?? null,
                            );
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('Échec de la clôture')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        $ecart = (float) $data['solde_cloture_reel'] - (float) $data['solde_cloture_theorique'];

                        Notification::make()
                            ->title('Session clôturée')
                            ->body($ecart === 0.0
                                ? 'Aucun écart constaté.'
                                : 'Écart constaté : ' . number_format($ecart, 2) . ' EUR.')
                            ->color($ecart === 0.0 ? 'success' : 'warning')
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
