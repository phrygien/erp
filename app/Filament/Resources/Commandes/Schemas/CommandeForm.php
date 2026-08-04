<?php

namespace App\Filament\Resources\Commandes\Schemas;

use App\Models\Magasin;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Livewire\Component as LivewireComponent;

class CommandeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([

                    // ================= STEP 1 : Informations commande =================
                    Step::make('Informations')
                        ->schema([
                            TextInput::make('libelle')
                                ->required()
                                ->columnSpanFull(),

                            Select::make('fournisseur_id')
                                ->label('Fournisseur')
                                ->relationship('fournisseur', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('magasin_id')
                                ->label('Magasin')
                                ->relationship('magasin', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            TextInput::make('montant_minimum')
                                ->label('Montant minimum')
                                ->numeric(),

                            TextInput::make('remise_facture')
                                ->label('Remise facture')
                                ->numeric()
                                ->default(0)
                                ->required(),

                            TextInput::make('nombre_jours')
                                ->label('Délai (jours)')
                                ->numeric(),

                            ToggleButtons::make('etat_commande')
                                ->label('État')
                                ->options([
                                    'pre_commande' => 'Pré-commande',
                                    'commande' => 'Commande',
                                ])
                                ->icons([
                                    'pre_commande' => Heroicon::OutlinedClock,
                                    'commande' => Heroicon::OutlinedCheckCircle,
                                ])
                                ->default('pre_commande')
                                ->inline()
                                ->live()
                                ->required(),

                            ToggleButtons::make('statut_commande')
                                ->label('Statut')
                                ->options([
                                    'annule' => 'Annulé',
                                    'cree' => 'Créé',
                                    'facturee' => 'Facturée',
                                    'cloturee' => 'Clôturée',
                                ])
                                ->icons([
                                    'annule' => Heroicon::OutlinedXCircle,
                                    'cree' => Heroicon::OutlinedCheckCircle,
                                    'facturee' => Heroicon::OutlinedDocumentText,
                                    'cloturee' => Heroicon::OutlinedCalculator,
                                ])
                                ->colors([
                                    'annule' => 'danger',
                                    'cree' => 'info',
                                    'facturee' => 'warning',
                                    'cloturee' => 'success',
                                ])
                                ->disableOptionWhen(fn (string $value, callable $get): bool => in_array($value, ['facturee', 'cloturee'])
                                    && $get('etat_commande') === 'pre_commande')
                                ->default('cree')
                                ->inline()
                                ->required(),
                        ])
                        ->columns(2),

                    // ================= STEP 2 : Sélection produits + répartition =================
                    Step::make('Produits')
                        ->schema([
                            Repeater::make('items')
                                ->label('Produits sélectionnés')
                                ->addActionLabel('Ajouter un produit')
                                ->live()
                                ->table([
                                    TableColumn::make('Produit'),
                                    TableColumn::make('PU Achat HT')->width('130px'),
                                    TableColumn::make('Taxe %')->width('90px'),
                                    TableColumn::make('Remise %')->width('90px'),
                                    TableColumn::make('Quantité')->width('90px'),
                                    TableColumn::make('PU Achat Net')->width('130px'),
                                ])
                                ->schema([
                                    Select::make('product_id')
                                        ->options(function (callable $get) {
                                            $selectedElsewhere = collect($get('../*.product_id') ?? [])
                                                ->filter()
                                                ->reject(fn ($id) => (string) $id === (string) $get('product_id'))
                                                ->all();

                                            return Product::query()
                                                ->where('state', 'active')
                                                ->when($selectedElsewhere, fn ($query) => $query->whereNotIn('id', $selectedElsewhere))
                                                ->get()
                                                ->mapWithKeys(fn ($p) => [$p->id => $p->designation]);
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            if ($state) {
                                                $product = Product::find($state);

                                                if ($product) {
                                                    $set('pu_achat_HT', $product->pght_parkod);
                                                    $set('tax', $product->tva);
                                                }
                                            }
                                        }),

                                    TextInput::make('pu_achat_HT')
                                        ->numeric()
                                        ->required()
                                        ->live(),

                                    TextInput::make('tax')
                                        ->numeric()
                                        ->default(0)
                                        ->required()
                                        ->live(),

                                    TextInput::make('taux_remise')
                                        ->numeric()
                                        ->default(0)
                                        ->required()
                                        ->live(),

                                    TextInput::make('quantite')
                                        ->numeric()
                                        ->live()
                                        ->readOnly(),

                                    Placeholder::make('pu_achat_net_display')
                                        ->content(function (callable $get) {
                                            $ht = (float) ($get('pu_achat_HT') ?? 0);
                                            $tax = (float) ($get('tax') ?? 0);
                                            $remise = (float) ($get('taux_remise') ?? 0);
                                            $net = $ht + ($ht * $tax / 100) - ($ht * $remise / 100);

                                            return number_format($net, 2) . ' MUR';
                                        }),
                                ])
                                ->extraItemActions([
                                    Action::make('repartir')
                                        ->label('Répartir')
                                        ->icon(Heroicon::OutlinedArrowsRightLeft)
                                        ->color('primary')
                                        ->modalHeading('Répartition par magasin')
                                        ->fillForm(function (array $arguments, Repeater $component) {
                                            $item = $component->getItemState($arguments['item']);

                                            return collect(Magasin::query()->where('active', true)->get())
                                                ->mapWithKeys(fn ($m) => [
                                                    "repartition_{$m->id}" => $item["repartition_{$m->id}"] ?? 0,
                                                ])
                                                ->all();
                                        })
                                        ->schema(fn () => Magasin::query()
                                            ->where('active', true)
                                            ->get()
                                            ->map(fn ($magasin) => TextInput::make("repartition_{$magasin->id}")
                                                ->label($magasin->name)
                                                ->numeric()
                                                ->default(0)
                                                ->required())
                                            ->all())
                                        ->action(function (array $data, array $arguments, LivewireComponent $livewire) {
                                            $magasins = Magasin::query()->where('active', true)->get();
                                            $itemKey = $arguments['item'];

                                            foreach ($magasins as $magasin) {
                                                data_set(
                                                    $livewire->data,
                                                    "items.{$itemKey}.repartition_{$magasin->id}",
                                                    $data["repartition_{$magasin->id}"] ?? 0
                                                );
                                            }

                                            $total = $magasins->sum(fn ($m) => (float) ($data["repartition_{$m->id}"] ?? 0));

                                            data_set($livewire->data, "items.{$itemKey}.quantite", $total);
                                        }),
                                ]),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable(false),
            ]);
    }
}
