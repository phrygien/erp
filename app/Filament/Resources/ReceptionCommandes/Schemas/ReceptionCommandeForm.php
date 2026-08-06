<?php

namespace App\Filament\Resources\ReceptionCommandes\Schemas;

use App\Models\BonCommande;
use App\Models\DetailCommande;
use App\Models\ReceptionCommande;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class ReceptionCommandeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Commande liée')
                    ->description('Informations sur la commande et le bon de commande concernés')
                    ->columns(2)
                    ->components([
                        TextInput::make('numero_reception')
                            ->label('N° de réception')
                            ->default(fn (?ReceptionCommande $record) => $record?->numero_reception
                                ?? ReceptionCommande::previewProchainNumero())
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Généré automatiquement'),

                        DatePicker::make('date_reception')
                            ->label('Date de réception')
                            ->required()
                            ->default(now()),

                        Select::make('commande_id')
                            ->label('Commande')
                            ->relationship('commande', 'numero_commande')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                $set('bon_commande_id', null);

                                if (! $state) {
                                    $set('details', []);
                                    return;
                                }

                                $lignes = DetailCommande::query()
                                    ->where('commande_id', $state)
                                    ->get()
                                    ->map(fn (DetailCommande $detail) => [
                                        'detail_commande_id' => $detail->id,
                                        'product_id' => $detail->product_id,
                                        'qte_commandee' => $detail->quantite,
                                        'qte_recue' => 0,
                                        'qte_invendable' => 0,
                                        'qte_vendable' => 0,
                                        'motif_invendable' => null,
                                        'commentaire' => null,
                                    ])
                                    ->values()
                                    ->toArray();

                                $set('details', $lignes);
                            }),

                        Select::make('bon_commande_id')
                            ->label('Bon de commande')
                            ->options(function (callable $get) {
                                $commandeId = $get('commande_id');

                                if (! $commandeId) {
                                    return [];
                                }

                                return BonCommande::query()
                                    ->where('commande_id', $commandeId)
                                    ->pluck('numero', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->disabled(fn (callable $get) => ! $get('commande_id'))
                            ->helperText('Sélectionnez d\'abord une commande')
                            ->nullable(),

                        TextInput::make('numero_bl')
                            ->label('N° bon de livraison'),
                    ]),

                Section::make('Suivi de la réception')
                    ->description('Statut, responsable et remarques')
                    ->columns(2)
                    ->components([
                        ToggleButtons::make('statut')
                            ->label('Statut')
                            ->options([
                                ReceptionCommande::STATUT_EN_COURS => 'En cours',
                                ReceptionCommande::STATUT_PARTIELLE => 'Partielle',
                                ReceptionCommande::STATUT_COMPLETE => 'Complète',
                                ReceptionCommande::STATUT_ANNULEE => 'Annulée',
                            ])
                            ->colors([
                                ReceptionCommande::STATUT_EN_COURS => 'warning',
                                ReceptionCommande::STATUT_PARTIELLE => 'info',
                                ReceptionCommande::STATUT_COMPLETE => 'success',
                                ReceptionCommande::STATUT_ANNULEE => 'danger',
                            ])
                            ->icons([
                                ReceptionCommande::STATUT_EN_COURS => 'heroicon-o-clock',
                                ReceptionCommande::STATUT_PARTIELLE => 'heroicon-o-arrow-path',
                                ReceptionCommande::STATUT_COMPLETE => 'heroicon-o-check-circle',
                                ReceptionCommande::STATUT_ANNULEE => 'heroicon-o-x-circle',
                            ])
                            ->required()
                            ->default(ReceptionCommande::STATUT_EN_COURS)
                            ->inline()
                            ->columnSpanFull(),

                        Select::make('received_by')
                            ->label('Réceptionné par')
                            ->relationship('receivedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->id()),

                        Textarea::make('commentaire')
                            ->label('Commentaire')
                            ->columnSpanFull(),
                    ]),

                Section::make('Détail de la réception')
                    ->description('Les lignes se chargent automatiquement selon la commande sélectionnée')
                    ->columnSpanFull()
                    ->visible(fn (?ReceptionCommande $record) => $record !== null)
                    ->components([
                        Repeater::make('details')
                            ->relationship()
                            ->label('')
                            ->afterStateHydrated(function (Repeater $component, callable $get, $state) {
                                // Si des lignes existent déjà en base, on les enrichit avec
                                // qte_commandee et qte_vendable, qui ne sont pas stockées en base
                                // (colonnes calculées / dérivées).
                                if (filled($state)) {
                                    $detailCommandeIds = collect($state)
                                        ->pluck('detail_commande_id')
                                        ->filter()
                                        ->all();

                                    $quantitesCommandees = DetailCommande::query()
                                        ->whereIn('id', $detailCommandeIds)
                                        ->pluck('quantite', 'id');

                                    $state = collect($state)
                                        ->map(function (array $ligne) use ($quantitesCommandees) {
                                            $qteRecue = (int) ($ligne['qte_recue'] ?? 0);
                                            $qteInvendable = (int) ($ligne['qte_invendable'] ?? 0);

                                            $ligne['qte_commandee'] = $quantitesCommandees[$ligne['detail_commande_id']] ?? null;
                                            $ligne['qte_vendable'] = max($qteRecue - $qteInvendable, 0);

                                            return $ligne;
                                        })
                                        ->values()
                                        ->toArray();

                                    $component->state($state);

                                    return;
                                }

                                $commandeId = $get('commande_id');

                                if (! $commandeId) {
                                    return;
                                }

                                $lignes = DetailCommande::query()
                                    ->where('commande_id', $commandeId)
                                    ->get()
                                    ->map(fn (DetailCommande $detail) => [
                                        'detail_commande_id' => $detail->id,
                                        'product_id' => $detail->product_id,
                                        'qte_commandee' => $detail->quantite,
                                        'qte_recue' => 0,
                                        'qte_invendable' => 0,
                                        'qte_vendable' => 0,
                                        'motif_invendable' => null,
                                        'commentaire' => null,
                                    ])
                                    ->values()
                                    ->toArray();

                                $component->state($lignes);
                            })
                            ->table([
                                TableColumn::make('Produit')
                                    ->width('35%'),
                                TableColumn::make('Qté commandée')
                                    ->width('15%'),
                                TableColumn::make('Qté reçue')
                                    ->width('15%'),
                                TableColumn::make('Qté invendable')
                                    ->width('15%'),
                                TableColumn::make('Qté vendable')
                                    ->width('15%'),
                            ])
                            ->schema([
                                Select::make('detail_commande_id')
                                    ->options(function (callable $get) {
                                        $commandeId = $get('../../commande_id');

                                        if (! $commandeId) {
                                            return [];
                                        }

                                        $lignesActuelles = collect($get('../../details') ?? []);
                                        $idActuel = $get('detail_commande_id');

                                        $idsDejaUtilises = $lignesActuelles
                                            ->pluck('detail_commande_id')
                                            ->filter()
                                            ->reject(fn ($id) => $id == $idActuel)
                                            ->toArray();

                                        return DetailCommande::query()
                                            ->where('commande_id', $commandeId)
                                            ->whereNotIn('id', $idsDejaUtilises)
                                            ->with('product')
                                            ->get()
                                            ->mapWithKeys(function (DetailCommande $detail) {
                                                $designation = $detail->product?->designation ?? '—';
                                                $ean = $detail->product?->EAN ?? '—';

                                                return [
                                                    $detail->id => sprintf(
                                                        '<div class="flex flex-col leading-tight py-0.5" title="%s"><span class="font-medium text-sm truncate">%s</span><span class="text-xs text-gray-400">EAN: %s</span></div>',
                                                        e($designation),
                                                        e($designation),
                                                        e($ean),
                                                    ),
                                                ];
                                            });
                                    })
                                    ->allowHtml()
                                    ->extraAttributes(['class' => 'min-w-0'])
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (! $state) {
                                            $set('product_id', null);
                                            $set('qte_commandee', null);
                                            return;
                                        }

                                        $detailCommande = DetailCommande::find($state);
                                        $set('product_id', $detailCommande?->product_id);
                                        $set('qte_commandee', $detailCommande?->quantite);
                                    }),

                                TextInput::make('qte_commandee')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false),

                                TextInput::make('qte_recue')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $qteRecue = (int) ($state ?? 0);
                                        $qteInvendable = (int) ($get('qte_invendable') ?? 0);

                                        $set('qte_vendable', max($qteRecue - $qteInvendable, 0));
                                    }),

                                TextInput::make('qte_invendable')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $qteRecue = (int) ($get('qte_recue') ?? 0);
                                        $qteInvendable = (int) ($state ?? 0);

                                        $set('qte_vendable', max($qteRecue - $qteInvendable, 0));
                                    }),

                                TextInput::make('qte_vendable')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(false),

                                // Champs non affichés en colonnes, gérés via la modale "Détails"
                                Hidden::make('product_id')
                                    ->required()
                                    ->dehydrated(),

                                TextInput::make('motif_invendable')
                                    ->hidden()
                                    ->dehydrated(),

                                Textarea::make('commentaire')
                                    ->hidden()
                                    ->dehydrated(),
                            ])
                            ->extraItemActions([
                                Action::make('details')
                                    ->label('Détails')
                                    ->icon('heroicon-o-arrow-top-right-on-square')
                                    ->color('gray')
                                    ->schema(fn (): array => [
                                        TextInput::make('motif_invendable')
                                            ->label('Motif invendable'),
                                        Textarea::make('commentaire')
                                            ->label('Commentaire'),
                                    ])
                                    ->fillForm(fn (array $arguments, Repeater $component): array => $component
                                        ->getItemState($arguments['item'])
                                    )
                                    ->action(function (array $arguments, array $data, Repeater $component) {
                                        $livewire = $component->getLivewire();
                                        $statePath = $component->getStatePath();

                                        $items = data_get($livewire, $statePath, []);
                                        $items[$arguments['item']] = array_merge(
                                            $items[$arguments['item']] ?? [],
                                            $data,
                                        );

                                        data_set($livewire, $statePath, $items);
                                    }),
                            ])
                            ->addActionLabel('Ajouter une ligne manuellement')
                            ->defaultItems(0)
                            ->reorderable()
                            ->deletable(),
                    ]),
            ]);
    }
}
