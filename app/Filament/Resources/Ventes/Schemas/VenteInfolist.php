<?php

namespace App\Filament\Resources\Ventes\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VenteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('numero_vente')
                            ->columnSpanFull(),
                        TextEntry::make('canal_vente')
                            ->columnSpanFull(),
                        TextEntry::make('montant_total')
                            ->numeric(),
                        TextEntry::make('magasin.name')
                            ->label('Magasin'),
                        TextEntry::make('caisseSession.caisse.numero_caisse')
                            ->label('Caisse')
                            ->placeholder('-'),
                        TextEntry::make('caisseSession.responsable.name')
                            ->label('Responsable caisse')
                            ->placeholder('-'),
                        TextEntry::make('caisseSession.ouverte_le')
                            ->label('Session ouverte le')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('caisseSession.statut')
                            ->label('Statut session')
                            ->placeholder('-'),
                        TextEntry::make('created_at')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->numeric()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
