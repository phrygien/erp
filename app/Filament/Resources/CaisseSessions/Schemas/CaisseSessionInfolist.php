<?php

namespace App\Filament\Resources\CaisseSessions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CaisseSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('caisse.name')
                    ->label('Caisse'),
                TextEntry::make('responsable.name')
                    ->label('Responsable'),
                TextEntry::make('date_session')
                    ->date(),
                TextEntry::make('ouverte_le')
                    ->dateTime(),
                TextEntry::make('fermee_le')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('solde_ouverture')
                    ->numeric(),
                TextEntry::make('solde_cloture_theorique')
                    ->numeric(),
                TextEntry::make('solde_cloture_reel')
                    ->numeric(),
                TextEntry::make('ecart')
                    ->numeric(),
                TextEntry::make('statut'),
                TextEntry::make('commentaire')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
