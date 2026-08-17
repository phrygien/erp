<?php

namespace App\Filament\Resources\CaisseSessions\Schemas;

use App\Models\Caisse;
use App\Models\Magasin;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CaisseSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('magasin_id')
                            ->label('Magasin')
                            ->options(fn () => Magasin::query()->where('active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            // Pas de colonne magasin_id sur caisse_sessions :
                            // ce champ ne sert qu'à filtrer les caisses
                            // disponibles ci-dessous, il n'est jamais envoyé
                            // au modèle.
                            ->dehydrated(false)
                            // En édition, on pré-remplit ce sélecteur depuis
                            // la caisse déjà associée à la session, pour ne
                            // pas forcer l'utilisateur à re-choisir le
                            // magasin à chaque fois qu'il ouvre le
                            // formulaire.
                            ->afterStateHydrated(function (Select $component, ?Model $record) {
                                if ($record?->caisse_id) {
                                    $component->state($record->caisse?->magasin_id);
                                }
                            })
                            ->required(),

                        Select::make('caisse_id')
                            ->label('Caisse')
                            ->options(function (callable $get) {
                                $magasinId = $get('magasin_id');

                                if (! $magasinId) {
                                    return [];
                                }

                                return Caisse::query()
                                    ->where('magasin_id', $magasinId)
                                    ->where('active', true)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            // Désactivé tant qu'aucun magasin n'est choisi :
                            // évite de proposer toutes les caisses de tous
                            // les magasins avant que le filtre soit appliqué.
                            ->disabled(fn (callable $get): bool => ! $get('magasin_id'))
                            ->required()
                            ->live(),

                        Select::make('responsable_id')
                            ->label('Responsable')
                            ->relationship('responsable', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        DatePicker::make('date_session')
                            ->label('Date de la session')
                            ->required()
                            ->default(now()),

                        DateTimePicker::make('ouverte_le')
                            ->label('Ouverte le')
                            ->required()
                            ->default(now()),

                        DateTimePicker::make('fermee_le')
                            ->label('Fermée le'),

                        TextInput::make('solde_ouverture')
                            ->label('Solde à l\'ouverture')
                            ->required()
                            ->numeric()
                            ->default(0),

                        TextInput::make('solde_cloture_theorique')
                            ->label('Solde théorique à la clôture')
                            ->numeric(),

                        TextInput::make('solde_cloture_reel')
                            ->label('Solde réel à la clôture')
                            ->numeric(),

                        TextInput::make('ecart')
                            ->numeric(),

                        Select::make('statut')
                            ->options([
                                'ouverte' => 'Ouverte',
                                'fermee' => 'Fermée',
                            ])
                            ->required()
                            ->default('ouverte'),

                        Textarea::make('commentaire')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
