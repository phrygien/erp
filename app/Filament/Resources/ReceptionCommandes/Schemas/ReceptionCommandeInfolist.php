<?php

namespace App\Filament\Resources\ReceptionCommandes\Schemas;

use App\Models\ReceptionCommande;
use Carbon\Carbon;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReceptionCommandeInfolist
{
    protected const MOIS_FR = [
        1 => 'Janv', 2 => 'Févr', 3 => 'Mars', 4 => 'Avr',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Août',
        9 => 'Sept', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc',
    ];

    protected static function formatDateFr(?string $state, bool $withTime = false): ?string
    {
        if (blank($state)) {
            return null;
        }

        $date = Carbon::parse($state);
        $formatted = $date->day . ' ' . self::MOIS_FR[$date->month] . ', ' . $date->year;

        if ($withTime) {
            $formatted .= ' à ' . $date->format('H:i');
        }

        return $formatted;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Réception')
                    ->description('Informations générales de la réception')
                    ->columns(4)
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('numero_reception')
                            ->label('N° de réception')
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('statut')
                            ->label('Statut')
                            ->badge()
                            ->formatStateUsing(fn (string $state) => match ($state) {
                                ReceptionCommande::STATUT_EN_COURS => 'En cours',
                                ReceptionCommande::STATUT_PARTIELLE => 'Partielle',
                                ReceptionCommande::STATUT_COMPLETE => 'Complète',
                                ReceptionCommande::STATUT_ANNULEE => 'Annulée',
                                default => $state,
                            })
                            ->color(fn (string $state) => match ($state) {
                                ReceptionCommande::STATUT_EN_COURS => 'warning',
                                ReceptionCommande::STATUT_PARTIELLE => 'info',
                                ReceptionCommande::STATUT_COMPLETE => 'success',
                                ReceptionCommande::STATUT_ANNULEE => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('date_reception')
                            ->label('Date de réception')
                            ->formatStateUsing(fn ($state) => self::formatDateFr($state)),

                        TextEntry::make('numero_bl')
                            ->label('N° bon de livraison')
                            ->badge()
                            ->color('gray')
                            ->placeholder('-'),

                        TextEntry::make('commande.numero_commande')
                            ->label('Commande')
                            ->url(fn (ReceptionCommande $record) => $record->commande_id
                                ? route('filament.admin.resources.commandes.view', $record->commande_id)
                                : null)
                            ->color(fn (ReceptionCommande $record) => $record->commande_id ? 'primary' : 'gray')
                            ->placeholder('-'),

                        TextEntry::make('bonCommande.numero')
                            ->label('Bon de commande')
                            ->placeholder('-'),

                        TextEntry::make('receivedBy.name')
                            ->label('Réceptionné par')
                            ->placeholder('-'),

                        TextEntry::make('commentaire')
                            ->label('Commentaire')
                            ->placeholder('Aucun commentaire')
                            ->columnSpan(4),
                    ]),

                Section::make('Résumé des quantités')
                    ->columns(3)
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('details_count')
                            ->label('Lignes reçues')
                            ->state(fn (ReceptionCommande $record) => $record->details()->count())
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('qte_totale_recue')
                            ->label('Total qté reçue')
                            ->state(fn (ReceptionCommande $record) => $record->qte_totale_recue)
                            ->badge()
                            ->color('success'),

                        TextEntry::make('qte_totale_invendable')
                            ->label('Total qté invendable')
                            ->state(fn (ReceptionCommande $record) => $record->qte_totale_invendable)
                            ->badge()
                            ->color(fn (ReceptionCommande $record) => $record->qte_totale_invendable > 0 ? 'danger' : 'gray'),
                    ]),

                Section::make('Détail de la réception')
                    ->description('Lignes de produits reçus, avec quantités et remarques')
                    ->columnSpanFull()
                    ->components([
                        RepeatableEntry::make('details')
                            ->label('')
                            ->table([
                                TableColumn::make('Produit')
                                    ->width('25%'),
                                TableColumn::make('EAN')
                                    ->width('15%'),
                                TableColumn::make('Qté reçue')
                                    ->width('12%'),
                                TableColumn::make('Qté invendable')
                                    ->width('13%'),
                                TableColumn::make('Qté vendable')
                                    ->width('13%'),
                                TableColumn::make('Motif')
                                    ->width('11%'),
                                TableColumn::make('Commentaire')
                                    ->width('11%'),
                            ])
                            ->schema([
                                TextEntry::make('product.designation')
                                    ->label('Produit')
                                    ->weight('medium')
                                    ->placeholder('-'),

                                TextEntry::make('product.EAN')
                                    ->label('EAN')
                                    ->color('gray')
                                    ->placeholder('-'),

                                TextEntry::make('qte_recue')
                                    ->label('Qté reçue')
                                    ->badge()
                                    ->color('success'),

                                TextEntry::make('qte_invendable')
                                    ->label('Qté invendable')
                                    ->badge()
                                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),

                                TextEntry::make('qte_bonne')
                                    ->label('Qté vendable')
                                    ->state(fn ($record) => $record->qte_bonne)
                                    ->badge()
                                    ->color('info'),

                                TextEntry::make('motif_invendable')
                                    ->label('Motif')
                                    ->placeholder('-')
                                    ->color('gray'),

                                TextEntry::make('commentaire')
                                    ->label('Commentaire')
                                    ->placeholder('-')
                                    ->color('gray'),
                            ])
                            ->contained(false),
                    ]),

                Section::make('Historique')
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsed()
                    ->components([
                        TextEntry::make('created_at')
                            ->label('Créée le')
                            ->formatStateUsing(fn ($state) => self::formatDateFr($state, withTime: true))
                            ->placeholder('-'),

                        TextEntry::make('updated_at')
                            ->label('Modifiée le')
                            ->formatStateUsing(fn ($state) => self::formatDateFr($state, withTime: true))
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
