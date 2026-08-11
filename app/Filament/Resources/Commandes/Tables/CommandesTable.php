<?php

namespace App\Filament\Resources\Commandes\Tables;

use App\Mail\BonCommandeMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class CommandesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_commande')
                    ->label('N° Commande')
                    ->searchable(isIndividual: true)
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Numéro copié'),

                TextColumn::make('fournisseur.name')
                    ->label('Fournisseur')
                    ->searchable(isIndividual: true)
                    ->sortable(),

                TextColumn::make('magasin.name')
                    ->label('Magasin')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('montant_total')
                    ->label('Montant total')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' EUR')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('etat_commande')
                    ->label('État')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pre_commande' => 'Pré-commande',
                        'commande' => 'Commande',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'pre_commande' => 'warning',
                        'commande' => 'info',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('statut_commande')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'annule' => 'Annulé',
                        'cree' => 'Créé',
                        'facturee' => 'Facturée',
                        'cloturee' => 'Clôturée',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'annule' => 'danger',
                        'cree' => 'gray',
                        'facturee' => 'info',
                        'cloturee' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('montant_minimum')
                    ->label('Montant min.')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('remise_facture')
                    ->label('Remise')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('createdBy.name')
                    ->label('Créé par')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Modifiée le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('fournisseur_id')
                    ->label('Fournisseur')
                    ->relationship('fournisseur', 'name')
                    ->searchable(),

                SelectFilter::make('magasin_id')
                    ->label('Magasin')
                    ->relationship('magasin', 'name')
                    ->searchable(),

                SelectFilter::make('etat_commande')
                    ->label('État')
                    ->options([
                        'pre_commande' => 'Pré-commande',
                        'commande' => 'Commande',
                    ]),

                SelectFilter::make('statut_commande')
                    ->label('Statut')
                    ->options([
                        'annule' => 'Annulé',
                        'cree' => 'Créé',
                        'facturee' => 'Facturée',
                        'cloturee' => 'Clôturée',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('telechargerBonCommande')
                        ->label('Télécharger bon de commande (PDF)')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->color('success')
                        ->visible(fn ($record) => $record->bonCommande !== null)
                        ->action(function ($record) {
                            $bonCommande = $record->bonCommande;

                            $pdf = Pdf::loadView('pdf.bon-commande', [
                                'bonCommande' => $bonCommande,
                                'commande' => $record,
                            ]);

                            return response()->streamDownload(
                                fn () => print ($pdf->output()),
                                "bon-commande-{$bonCommande->numero}.pdf"
                            );
                        }),

                    Action::make('envoyerBonCommande')
                        ->label('Envoyer au fournisseur')
                        ->icon(Heroicon::OutlinedEnvelope)
                        ->color('info')
                        ->visible(fn ($record) => $record->bonCommande !== null)
                        ->disabled(fn ($record) => blank($record->fournisseur->email))
                        ->tooltip(fn ($record) => blank($record->fournisseur->email)
                            ? 'Aucune adresse e-mail renseignée pour ce fournisseur'
                            : null)
                        ->requiresConfirmation()
                        ->modalHeading('Envoyer le bon de commande')
                        ->modalDescription(fn ($record) => "Le bon de commande sera envoyé par e-mail à {$record->fournisseur->name} ({$record->fournisseur->email}).")
                        ->modalSubmitActionLabel('Envoyer')
                        ->action(function ($record) {
                            $bonCommande = $record->bonCommande;

                            $pdf = Pdf::loadView('pdf.bon-commande', [
                                'bonCommande' => $bonCommande,
                                'commande' => $record,
                            ]);

                            Mail::to($record->fournisseur->email)->send(
                                new BonCommandeMail(
                                    commande: $record,
                                    pdfContent: $pdf->output(),
                                    pdfFilename: "bon-commande-{$bonCommande->numero}.pdf",
                                )
                            );

                            Notification::make()
                                ->title('Bon de commande envoyé avec succès')
                                ->body("Envoyé à {$record->fournisseur->email}")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
