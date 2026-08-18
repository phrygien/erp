<?php

namespace App\Filament\Resources\CaisseSessions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CaisseSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Session de caisse')
                    ->columns(2)
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        TextEntry::make('caisse.name')
                            ->label('Caisse'),

                        TextEntry::make('responsable.name')
                            ->label('Responsable'),

                        TextEntry::make('date_session')
                            ->date(),

                        TextEntry::make('statut')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'ouverte' => 'success',
                                'fermee' => 'gray',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'ouverte' => 'Ouverte',
                                'fermee' => 'Fermée',
                                default => $state,
                            }),

                        TextEntry::make('ouverte_le')
                            ->dateTime(),

                        TextEntry::make('fermee_le')
                            ->dateTime()
                            ->placeholder('-'),

                        TextEntry::make('solde_ouverture')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' EUR'),

                        TextEntry::make('solde_cloture_theorique')
                            ->label('Solde théorique')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' EUR')
                            ->placeholder('-'),

                        TextEntry::make('solde_cloture_reel')
                            ->label('Solde réel')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' EUR')
                            ->placeholder('-'),

                        TextEntry::make('ecart')
                            ->numeric(decimalPlaces: 2)
                            ->suffix(' EUR')
                            ->placeholder('-')
                            ->badge()
                            ->color(fn (?string $state): string => match (true) {
                                $state === null => 'gray',
                                (float) $state === 0.0 => 'success',
                                default => 'danger',
                            }),

                        TextEntry::make('commentaire')
                            ->placeholder('-')
                            ->columnSpanFull(),

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
