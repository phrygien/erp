<?php

namespace App\Filament\Resources\Commandes\RelationManagers;

use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StatusHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistories';

    protected static ?string $title = 'Historique des statuts';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedClock;

    public function form(Schema $schema): Schema
    {
        // Lecture seule : l'historique est généré automatiquement,
        // aucun formulaire de création/édition n'est nécessaire.
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('champ')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('champ')
                    ->label('Champ modifié')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'etat_commande' => 'État',
                        'statut_commande' => 'Statut',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'etat_commande' => 'info',
                        'statut_commande' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('ancienne_valeur')
                    ->label('Ancienne valeur')
                    ->formatStateUsing(fn (?string $state): string => self::formatValeur($state))
                    ->color('danger')
                    ->placeholder('-'),

                TextColumn::make('nouvelle_valeur')
                    ->label('Nouvelle valeur')
                    ->formatStateUsing(fn (?string $state): string => self::formatValeur($state))
                    ->color('success'),

                TextColumn::make('changedBy.name')
                    ->label('Modifié par')
                    ->placeholder('Système'),

                TextColumn::make('commentaire')
                    ->label('Commentaire')
                    ->limit(40)
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->formatStateUsing(fn ($state) => $state?->locale('fr')->translatedFormat('d F Y \à H:i'))
                    ->sortable(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    protected static function formatValeur(?string $state): string
    {
        return match ($state) {
            'pre_commande' => 'Pré-commande',
            'commande' => 'Commande',
            'annule' => 'Annulé',
            'cree' => 'Créé',
            'facturee' => 'Facturée',
            'cloturee' => 'Clôturée',
            null => '-',
            default => $state,
        };
    }
}
