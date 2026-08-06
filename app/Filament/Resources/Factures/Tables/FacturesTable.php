<?php

namespace App\Filament\Resources\Factures\Tables;

use App\Models\Facture;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FacturesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_facture')
                    ->label('N° Facture')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Numéro copié')
                    ->weight('bold'),

                TextColumn::make('libelle_facture')
                    ->label('Libellé')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->libelle_facture),

                TextColumn::make('fournisseur.name')
                    ->label('Fournisseur')
                    ->sortable(),

                TextColumn::make('bonCommande.numero')
                    ->label('N° Bon de commande')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('date_facture')
                    ->label('Date facture')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('montant_ht')
                    ->label('Montant HT')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('remise')
                    ->label('Remise')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('montant_tva')
                    ->label('TVA')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('montant_ttc')
                    ->label('Montant TTC')
                    ->money('EUR')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'commande' => 'Commande',
                        'retour_commande' => 'Retour',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'commande' => 'info',
                        'retour_commande' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'encours' => 'En cours',
                        'paye' => 'Payée',
                        'rejete' => 'Rejetée',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'encours' => 'warning',
                        'paye' => 'success',
                        'rejete' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                IconColumn::make('archivage')
                    ->label('Archivée')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('createdBy.name')
                    ->label('Créée par')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('numero_facture')
                    ->label('N° Facture')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('numero_facture')
                            ->label('N° Facture'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['numero_facture'],
                            fn ($q, $value) => $q->where('numero_facture', $value)
                        );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['numero_facture']) {
                            return null;
                        }

                        return 'N° Facture: '.$data['numero_facture'];
                    }),

                SelectFilter::make('statut')
                    ->label('Statut')
                    ->options([
                        'encours' => 'En cours',
                        'paye' => 'Payée',
                        'rejete' => 'Rejetée',
                    ]),

                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'commande' => 'Commande',
                        'retour_commande' => 'Retour',
                    ]),

                SelectFilter::make('fournisseur_id')
                    ->label('Fournisseur')
                    ->relationship('fournisseur', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('archivage')
                    ->label('Archivée'),

                Filter::make('date_facture')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('date_debut')
                            ->label('Du'),
                        \Filament\Forms\Components\DatePicker::make('date_fin')
                            ->label('Au'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_debut'], fn ($q, $date) => $q->whereDate('date_facture', '>=', $date))
                            ->when($data['date_fin'], fn ($q, $date) => $q->whereDate('date_facture', '<=', $date));
                    }),

                Filter::make('en_retard')
                    ->label('Échéance dépassée')
                    ->query(fn (Builder $query) => $query
                        ->where('date_echeance', '<', now())
                        ->where('statut', '!=', 'paye')),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('pdf')
                        ->label('Facture PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('gray')
                        ->action(function (Facture $record) {
                            $pdf = Pdf::loadView('pdf.facture', [
                                'facture' => $record->load(['fournisseur', 'bonCommande', 'detailFactures.detailCommande.product']),
                            ]);

                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                "facture-{$record->numero_facture}.pdf"
                            );
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date_facture', 'desc');
    }
}
