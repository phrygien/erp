<?php

namespace App\Filament\Resources\Commandes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommandeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Commande')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('numero_commande')
                                    ->label('N° commande')
                                    ,

                                TextEntry::make('libelle')
                                    ->label('Libellé')
                                    ,

                                TextEntry::make('fournisseur.name')
                                    ->label('Fournisseur')

                                    ->placeholder('-'),

                                TextEntry::make('magasin.name')
                                    ->label('Magasin')

                                    ->placeholder('-'),

                                TextEntry::make('etat_commande')
                                    ->label('État')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'pre_commande' => 'Pré-commande',
                                        'commande' => 'Commande',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'pre_commande' => 'gray',
                                        'commande' => 'info',
                                        default => 'gray',
                                    }),

                                TextEntry::make('statut_commande')
                                    ->label('Statut')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'annule' => 'Annulé',
                                        'cree' => 'Créé',
                                        'facturee' => 'Facturée',
                                        'cloturee' => 'Clôturée',
                                        default => $state,
                                    })
                                    ->color(fn (string $state): string => match ($state) {
                                        'annule' => 'danger',
                                        'cree' => 'gray',
                                        'facturee' => 'warning',
                                        'cloturee' => 'success',
                                        default => 'gray',
                                    }),

                                TextEntry::make('montant_minimum')
                                    ->label('Montant minimum')
                                    ->numeric(decimalPlaces: 2)
                                    ->suffix(' MUR')

                                    ->placeholder('-'),

                                TextEntry::make('remise_facture')
                                    ->label('Remise facture')
                                    ->numeric(decimalPlaces: 2)
                                    ->suffix(' %')
                                    ,

                                TextEntry::make('nombre_jours')
                                    ->label('Délai (jours)')
                                    ->numeric()
                                    ->suffix(' j')

                                    ->placeholder('-'),

                                TextEntry::make('montant_total')
                                    ->label('Montant total')
                                    ->numeric(decimalPlaces: 2)
                                    ->suffix(' MUR')
                                    ->color('success'),

                                TextEntry::make('createdBy.name')
                                    ->label('Créé par')
                                    ->placeholder('-'),

                                TextEntry::make('created_at')
                                    ->label('Créé le')
                                    ->formatStateUsing(fn ($state) => $state?->locale('fr')->translatedFormat('d F Y \à H:i'))
                                    ->placeholder('-'),

                                TextEntry::make('updated_at')
                                    ->label('Modifié le')
                                    ->formatStateUsing(fn ($state) => $state?->locale('fr')->translatedFormat('d F Y \à H:i'))
                                    ->placeholder('-'),
                            ]),
                    ]),
            ]);
    }
}
