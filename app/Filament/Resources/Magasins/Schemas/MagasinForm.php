<?php

namespace App\Filament\Resources\Magasins\Schemas;

use App\Models\Magasin;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class MagasinForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom')
                            ->required(),

                        Select::make('type')
                            ->options([
                                'en ligne' => 'En ligne',
                                'physique' => 'Physique',
                            ])
                            ->required()
                            ->default('en ligne'),

                        Textarea::make('adresse')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('telephone')
                            ->tel()
                            ->required(),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),

                        Toggle::make('active')
                            ->label('Actif')
                            ->required(),

                        Toggle::make('depot_central')
                            ->label('Dépôt central')
                            ->helperText('Un seul magasin peut être défini comme dépôt central. C\'est le seul magasin pour lequel le stock est suivi.')
                            // Règle en closure plutôt que Rule::unique() : on
                            // contrôle directement le message via $fail(),
                            // sans dépendre du mapping de clés de
                            // ->validationMessages(), qui n'est pas garanti
                            // pour une règle objet personnalisée. On ne
                            // vérifie l'unicité que si la valeur soumise est
                            // true — false ne peut jamais entrer en conflit,
                            // plusieurs magasins pouvant avoir false.
                            ->rule(function (?Model $record): Closure {
                                return function (string $attribute, $value, Closure $fail) use ($record) {
                                    if (! $value) {
                                        return;
                                    }

                                    $existeDeja = Magasin::query()
                                        ->where('depot_central', true)
                                        ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                        ->exists();

                                    if ($existeDeja) {
                                        $fail('Un dépôt central existe déjà. Il ne peut y en avoir qu\'un seul.');
                                    }
                                };
                            })
                            ->default(false),
                    ]),
            ]);
    }
}
