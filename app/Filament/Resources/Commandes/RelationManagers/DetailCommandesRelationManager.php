<?php

namespace App\Filament\Resources\Commandes\RelationManagers;

use App\Models\Magasin;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DetailCommandesRelationManager extends RelationManager
{
    protected static string $relationship = 'detailCommandes';

    protected static ?string $title = 'Produits';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->label('Produit')
                    ->options(fn () => Product::query()
                        ->where('state', 'active')
                        ->get()
                        ->mapWithKeys(fn ($p) => [$p->id => $p->designation]))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state && $product = Product::find($state)) {
                            $set('pu_achat_HT', $product->pght_parkod);
                            $set('tax', $product->tva);
                        }
                    }),

                TextInput::make('pu_achat_HT')
                    ->label('PU Achat HT')
                    ->numeric()
                    ->required(),

                TextInput::make('tax')
                    ->label('Taxe %')
                    ->numeric()
                    ->default(0)
                    ->required(),

                TextInput::make('taux_remise')
                    ->label('Remise %')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_id')
            ->columns([
                TextColumn::make('product.designation')
                    ->label('Produit')
                    ->searchable(),

                TextColumn::make('pu_achat_HT')
                    ->label('PU Achat HT')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' MUR'),

                TextColumn::make('tax')
                    ->label('Taxe %')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' %'),

                TextColumn::make('taux_remise')
                    ->label('Remise %')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' %'),

                TextColumn::make('pu_achat_net')
                    ->label('PU Achat Net')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' MUR'),

                TextColumn::make('quantite')
                    ->label('Quantité')
                    ->numeric()
                    ->badge()
                    ->color('info'),

                TextColumn::make('repartitions_summary')
                    ->label('Répartition')
                    ->state(fn ($record) => $record->repartitions
                        ->map(fn ($r) => "{$r->magasin?->name}: {$r->quantite}")
                        ->join(' · ') ?: '—'),
            ])
            ->headerActions([
                CreateAction::make()->label('Ajouter un produit'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('repartir')
                        ->label('Répartir')
                        ->icon(Heroicon::OutlinedArrowsRightLeft)
                        ->color('primary')
                        ->modalHeading('Répartition par magasin')
                        ->fillForm(fn ($record) => Magasin::query()
                            ->where('active', true)
                            ->get()
                            ->mapWithKeys(fn ($m) => [
                                "repartition_{$m->id}" => $record->repartitions
                                        ->firstWhere('magasin_id', $m->id)?->quantite ?? 0,
                            ])
                            ->all())
                        ->schema(fn () => Magasin::query()
                            ->where('active', true)
                            ->get()
                            ->map(fn ($m) => TextInput::make("repartition_{$m->id}")
                                ->label($m->name)
                                ->numeric()
                                ->default(0)
                                ->required())
                            ->all())
                        ->action(function (array $data, $record) {
                            foreach (Magasin::query()->where('active', true)->get() as $magasin) {
                                $record->repartitions()->updateOrCreate(
                                    ['magasin_id' => $magasin->id],
                                    ['quantite' => (float) ($data["repartition_{$magasin->id}"] ?? 0)]
                                );
                            }
                        }),

                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
