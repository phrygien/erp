<?php

namespace App\Filament\Resources\ReceptionCommandes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReceptionCommandeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Réception')
                    ->columns(3)
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('numero_reception'),
                        TextEntry::make('commande.id')
                            ->label('Commande'),
                        TextEntry::make('bonCommande.id')
                            ->label('Bon commande')
                            ->placeholder('-'),
                        TextEntry::make('numero_bl')
                            ->placeholder('-'),
                        TextEntry::make('date_reception')
                            ->date(),
                        TextEntry::make('statut'),
                        TextEntry::make('received_by')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('commentaire')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
