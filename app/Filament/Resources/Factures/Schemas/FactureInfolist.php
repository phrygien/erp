<?php

namespace App\Filament\Resources\Factures\Schemas;

use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FactureInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Facture')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // -----------------------------------
                                // Colonne gauche : identité de la facture
                                // -----------------------------------
                                Group::make()
                                    ->schema([
                                        TextEntry::make('numero_facture')
                                            ->label('N° Facture')
                                            ->copyable()
                                            ->copyMessage('Numéro copié')
                                            ->weight('bold'),

                                        TextEntry::make('libelle_facture')
                                            ->label('Libellé'),

                                        ToggleButtons::make('type')
                                            ->label('Type')
                                            ->options(fn ($record) => [
                                                $record->type => match ($record->type) {
                                                    'commande' => 'Commande',
                                                    'retour_commande' => 'Retour de commande',
                                                    default => $record->type,
                                                },
                                            ])
                                            ->colors([
                                                'commande' => 'info',
                                                'retour_commande' => 'warning',
                                            ])
                                            ->inline()
                                            ->disabled()
                                            ->dehydrated(false),

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
                                    ]),

                                // -----------------------------------
                                // Colonne droite : origine et suivi
                                // -----------------------------------
                                Group::make()
                                    ->schema([
                                        TextEntry::make('fournisseur.name')
                                            ->label('Fournisseur'),

                                        TextEntry::make('bonCommande.numero')
                                            ->label('N° Bon de commande')
                                            ->placeholder('-'),

                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('date_facture')
                                                    ->label('Date facture')
                                                    ->date('d F Y'),

                                                TextEntry::make('date_echeance')
                                                    ->label('Date échéance')
                                                    ->date('d F Y')
                                                    ->placeholder('-')
                                                    ->color(fn ($record) => $record->date_echeance
                                                    && $record->date_echeance->isPast()
                                                    && $record->statut !== 'paye'
                                                        ? 'danger'
                                                        : null),
                                            ]),

                                        IconEntry::make('archivage')
                                            ->label('Archivée')
                                            ->boolean(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Totaux')
                    ->columns(5)
                    ->schema([
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
                    ])
                    ->columnSpanFull(),

                Section::make('Suivi')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('createdBy.name')
                            ->label('Créée par')
                            ->placeholder('-'),

                        TextEntry::make('created_at')
                            ->label('Créée le')
                            ->dateTime('d F Y à H:i')
                            ->placeholder('-'),

                        TextEntry::make('updated_at')
                            ->label('Modifiée le')
                            ->dateTime('d F Y à H:i')
                            ->placeholder('-'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
