<?php

namespace App\Filament\Resources\Magasins\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class MagasinInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nom')
                            ->weight('bold')
                            ->icon(Heroicon::BuildingStorefront),

                        TextEntry::make('type')
                            ->label('Type')
                            ->badge()
                            ->formatStateUsing(fn ($state) => ucfirst($state))
                            ->color(fn ($state) => $state === 'en ligne' ? 'info' : 'gray'),

                        TextEntry::make('adresse')
                            ->label('Adresse')
                            ->icon(Heroicon::MapPin)
                            ->columnSpanFull(),

                        TextEntry::make('telephone')
                            ->label('Téléphone')
                            ->icon(Heroicon::Phone)
                            ->copyable()
                            ->copyMessage('Téléphone copié'),

                        TextEntry::make('email')
                            ->label('Email')
                            ->icon(Heroicon::Envelope)
                            ->copyable()
                            ->copyMessage('Email copié'),

                        IconEntry::make('active')
                            ->label('Actif')
                            ->boolean(),

                        TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime()
                            ->placeholder('-'),

                        TextEntry::make('updated_at')
                            ->label('Modifié le')
                            ->dateTime()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
