<?php

namespace App\Filament\Resources\Fournisseurs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class FournisseurInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fournisseur')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nom')
                                    ->weight('bold')
                                    ->icon(Heroicon::BuildingOffice2),

                                TextEntry::make('code')
                                    ->badge(),

                                TextEntry::make('raison_social')
                                    ->label('Raison sociale')
                                    ->placeholder('-'),

                                TextEntry::make('state')
                                    ->label('Statut')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => $state === 'active' ? 'Actif' : 'Inactif')
                                    ->color(fn ($state) => $state === 'active' ? 'success' : 'danger'),

                                TextEntry::make('telephone')
                                    ->icon(Heroicon::Phone)
                                    ->copyable()
                                    ->copyMessage('Téléphone copié')
                                    ->placeholder('-'),

                                TextEntry::make('fax')
                                    ->placeholder('-'),

                                TextEntry::make('email')
                                    ->label('Email')
                                    ->icon(Heroicon::Envelope)
                                    ->copyable()
                                    ->copyMessage('Email copié')
                                    ->placeholder('-'),

                                TextEntry::make('adresse_siege')
                                    ->label('Adresse siège')
                                    ->icon(Heroicon::MapPin)
                                    ->placeholder('-'),

                                TextEntry::make('code_postal')
                                    ->label('Code postal')
                                    ->placeholder('-'),

                                TextEntry::make('ville')
                                    ->label('Ville')
                                    ->placeholder('-'),

                                TextEntry::make('adresse_retour')
                                    ->label('Adresse de retour')
                                    ->placeholder('-'),

                                TextEntry::make('code_postal_retour')
                                    ->label('Code postal (retour)')
                                    ->placeholder('-'),

                                TextEntry::make('ville_retour')
                                    ->label('Ville (retour)')
                                    ->placeholder('-'),

                                TextEntry::make('created_at')
                                    ->label('Créé le')
                                    ->dateTime()
                                    ->placeholder('-'),

                                TextEntry::make('updated_at')
                                    ->label('Modifié le')
                                    ->dateTime()
                                    ->placeholder('-'),
                            ]),
                    ]),
            ]);
    }
}
