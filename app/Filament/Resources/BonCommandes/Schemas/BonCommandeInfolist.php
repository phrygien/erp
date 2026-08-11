<?php

namespace App\Filament\Resources\BonCommandes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BonCommandeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bon de commande')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('numero'),
                        TextEntry::make('statut_commande')
                            ->badge(),
                        TextEntry::make('date_commande')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('date_livraison')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('magasinFacturation.name')
                            ->label('Magasin facturation'),
                        TextEntry::make('magasinLivraison.name')
                            ->label('Magasin livraison'),
                        TextEntry::make('montant_commande')
                            ->numeric()
                            ->money('MUR'),
                        TextEntry::make('code_fournisseur'),
                        TextEntry::make('numero_compte')
                            ->placeholder('-'),
                    ]),

                Section::make('Commande liée')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('commande.numero_commande')
                            ->label('N° commande')
                            ->placeholder('-'),
                        TextEntry::make('commande.libelle')
                            ->label('Libellé')
                            ->placeholder('-'),
                        TextEntry::make('commande.fournisseur.nom')
                            ->label('Fournisseur')
                            ->placeholder('-'),
                        TextEntry::make('commande.magasin.name')
                            ->label('Magasin')
                            ->placeholder('-'),
                        TextEntry::make('commande.montant_total')
                            ->label('Montant total')
                            ->numeric()
                            ->money('MUR')
                            ->placeholder('-'),
                        TextEntry::make('commande.etat_commande')
                            ->label('État')
                            ->badge()
                            ->placeholder('-'),
                    ]),

                Section::make('Métadonnées')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('createdBy.name')
                            ->label('Créé par')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
