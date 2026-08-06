<?php

namespace App\Filament\Resources\Factures\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FactureInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Facture')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('numero_facture')
                            ->label('N° Facture')
                            ->copyable()
                            ->copyMessage('Numéro copié')
                            ->weight('bold'),

                        TextEntry::make('type')
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

                        TextEntry::make('statut')
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
                            }),

                        TextEntry::make('libelle_facture')
                            ->label('Libellé')
                            ->columnSpanFull(),

                        TextEntry::make('fournisseur.name')
                            ->label('Fournisseur'),

                        TextEntry::make('bonCommande.numero')
                            ->label('N° Bon de commande')
                            ->placeholder('-'),

                        IconEntry::make('archivage')
                            ->label('Archivée')
                            ->boolean(),

                        TextEntry::make('date_facture')
                            ->label('Date facture')
                            ->date('d/m/Y'),

                        TextEntry::make('date_echeance')
                            ->label('Date échéance')
                            ->date('d/m/Y')
                            ->placeholder('-')
                            ->color(fn ($record) => $record->date_echeance
                            && $record->date_echeance->isPast()
                            && $record->statut !== 'paye'
                                ? 'danger'
                                : null),

                        TextEntry::make('montant_ht')
                            ->label('Montant HT')
                            ->money('EUR'),

                        TextEntry::make('taux_tva')
                            ->label('Taux TVA')
                            ->suffix(' %'),

                        TextEntry::make('montant_tva')
                            ->label('Montant TVA')
                            ->money('EUR'),

                        TextEntry::make('remise')
                            ->label('Remise')
                            ->money('EUR')
                            ->color('danger'),

                        TextEntry::make('montant_ttc')
                            ->label('Montant TTC')
                            ->money('EUR')
                            ->weight('bold')
                            ->size('lg')
                            ->color('primary'),

                        TextEntry::make('createdBy.name')
                            ->label('Créée par')
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label('Créée le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),

                        TextEntry::make('updated_at')
                            ->label('Modifiée le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
