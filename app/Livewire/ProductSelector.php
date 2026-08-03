<?php

namespace App\Livewire;

use App\Models\Magasin;
use App\Models\Product;
use Filament\Actions\Action as TableAction;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput as FormTextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class ProductSelector extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithForms;
    use InteractsWithTable;
    use InteractsWithActions;

    public array $items = []; // clé = product_id

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query()->where('state', 'active'))
            ->columns([
                TextColumn::make('designation')
                    ->label('Désignation')
                    ->searchable()
                    ->description(fn ($record) => $record->product_code),

                TextColumn::make('category.name')
                    ->label('Catégorie'),

                TextColumn::make('pght_parkod')
                    ->label('Prix catalogue')
                    ->formatStateUsing(fn ($state, $record) => number_format($state, 2) . ' ' . $record->devise),

                TextColumn::make('qte')
                    ->label('Qté commandée')
                    ->state(fn ($record) => $this->items[$record->id]['quantite'] ?? '-')
                    ->badge()
                    ->color(fn ($record) => isset($this->items[$record->id]) ? 'success' : 'gray'),
            ])
            ->searchable()
            ->recordActions([
                TableAction::make('toggle')
                    ->label(fn ($record) => isset($this->items[$record->id]) ? '✓ Sélectionné' : 'Sélectionner')
                    ->icon(fn ($record) => isset($this->items[$record->id])
                        ? 'heroicon-o-check-circle'
                        : 'heroicon-o-plus-circle')
                    ->color(fn ($record) => isset($this->items[$record->id]) ? 'success' : 'gray')
                    ->modalHeading('Détails et répartition par magasin')
                    ->fillForm(function ($record) {
                        $existing = $this->items[$record->id] ?? null;

                        $repartitionValues = [];
                        foreach (Magasin::query()->pluck('id') as $magasinId) {
                            $repartitionValues[$magasinId] = $existing['repartitions'][$magasinId] ?? 0;
                        }

                        return [
                            'pu_achat_HT' => $existing['pu_achat_HT'] ?? $record->pght_parkod,
                            'tax' => $existing['tax'] ?? $record->tva,
                            'taux_remise' => $existing['taux_remise'] ?? 0,
                            'quantite' => $existing['quantite'] ?? array_sum($repartitionValues),
                            'repartitions' => $repartitionValues,
                        ];
                    })
                    ->schema([
                        FormTextInput::make('pu_achat_HT')
                            ->label('PU Achat HT')
                            ->numeric()
                            ->required(),

                        FormTextInput::make('tax')
                            ->label('Taxe %')
                            ->numeric()
                            ->required(),

                        FormTextInput::make('taux_remise')
                            ->label('Remise %')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        FormTextInput::make('quantite')
                            ->label('Quantité totale')
                            ->numeric()
                            ->required()
                            ->disabled()
                            ->dehydrated(),

                        Section::make('Répartition par magasin')
                            ->columnSpanFull()
                            ->columns(2)
                            ->schema(fn () => Magasin::query()
                                ->orderBy('name')
                                ->get()
                                ->map(fn ($magasin) => FormTextInput::make("repartitions.{$magasin->id}")
                                    ->label($magasin->name)
                                    ->numeric()
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (callable $get, callable $set) {
                                        $total = collect($get('repartitions') ?? [])
                                            ->sum(fn ($qty) => (int) $qty);

                                        $set('quantite', $total);
                                    }))
                                ->all()),
                    ])
                    ->action(function (array $data, $record) {
                        $repartitions = collect($data['repartitions'] ?? [])
                            ->filter(fn ($qty) => (int) $qty > 0)
                            ->all();

                        $totalQuantite = array_sum($repartitions);

                        $this->items[$record->id] = [
                            'product_id' => $record->id,
                            'designation' => $record->designation,
                            'pu_achat_HT' => $data['pu_achat_HT'],
                            'tax' => $data['tax'],
                            'taux_remise' => $data['taux_remise'],
                            'quantite' => $totalQuantite,
                            'repartitions' => $repartitions,
                        ];

                        $this->dispatch('items-updated', items: array_values($this->items));
                    }),

                TableAction::make('remove')
                    ->label('Retirer')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => isset($this->items[$record->id]))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        unset($this->items[$record->id]);
                        $this->dispatch('items-updated', items: array_values($this->items));
                    }),
            ]);
    }

    public function render()
    {
        return view('livewire.product-selector');
    }
}
