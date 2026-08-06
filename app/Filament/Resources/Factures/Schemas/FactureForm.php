<?php

namespace App\Filament\Resources\Factures\Schemas;

use App\Models\BonCommande;
use App\Models\DetailCommande;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class FactureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([

                Section::make('Facture')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // -----------------------------------
                                // Colonne gauche : identité de la facture
                                // -----------------------------------
                                Group::make()
                                    ->schema([
                                        TextInput::make('numero_facture')
                                            ->label('N° Facture')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->default(fn () => strtoupper('FAC-' . Str::random(8))),

                                        TextInput::make('libelle_facture')
                                            ->label('Libellé')
                                            ->required(),

                                        ToggleButtons::make('type')
                                            ->label('Type')
                                            ->options([
                                                'commande' => 'Commande',
                                                'retour_commande' => 'Retour de commande',
                                            ])
                                            ->colors([
                                                'commande' => 'info',
                                                'retour_commande' => 'warning',
                                            ])
                                            ->required()
                                            ->default('commande')
                                            ->inline(),

                                        ToggleButtons::make('statut')
                                            ->label('Statut')
                                            ->options([
                                                'encours' => 'En cours',
                                                'paye' => 'Payée',
                                                'rejete' => 'Rejetée',
                                            ])
                                            ->colors([
                                                'encours' => 'warning',
                                                'paye' => 'success',
                                                'rejete' => 'danger',
                                            ])
                                            ->icons([
                                                'encours' => 'heroicon-o-clock',
                                                'paye' => 'heroicon-o-check-circle',
                                                'rejete' => 'heroicon-o-x-circle',
                                            ])
                                            ->required()
                                            ->default('encours')
                                            ->inline(),
                                    ]),

                                // -----------------------------------
                                // Colonne droite : origine et suivi
                                // -----------------------------------
                                Group::make()
                                    ->schema([
                                        Select::make('bon_commande_id')
                                            ->label('Bon de commande')
                                            ->relationship('bonCommande', 'numero')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live()
                                            // Dès qu'un bon de commande est choisi : on récupère le
                                            // fournisseur ET on génère automatiquement toutes les
                                            // lignes de facture depuis les detail_commandes liés
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $bonCommande = BonCommande::with('commande')->find($state);

                                                $set('fournisseur_id', $bonCommande?->commande?->fournisseur_id);

                                                self::genererLignesDepuisCommande($set, $bonCommande?->commande_id);
                                                self::recalculerTotauxFacture($set, $get);
                                            }),

                                        Select::make('fournisseur_id')
                                            ->label('Fournisseur')
                                            ->relationship('fournisseur', 'name')
                                            ->required()
                                            ->disabled()
                                            ->dehydrated(),

                                        Grid::make(2)
                                            ->schema([
                                                DatePicker::make('date_facture')
                                                    ->label('Date facture')
                                                    ->required()
                                                    ->default(now()),

                                                DatePicker::make('date_echeance')
                                                    ->label("Date d'échéance")
                                                    ->afterOrEqual('date_facture'),
                                            ]),

                                        Toggle::make('archivage')
                                            ->label('Archivée')
                                            ->default(false)
                                            ->inline(false),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Lignes de facturation')
                    ->description('Générées automatiquement depuis le bon de commande sélectionné. La quantité facturée reprend par défaut la quantité commandée.')
                    ->schema([
                        Repeater::make('detailFactures')
                            ->relationship()
                            ->label('')
                            ->addActionLabel('Ajouter une ligne manuellement')
                            ->schema([
                                Grid::make(16)
                                    ->schema([
                                        Select::make('detail_commande_id')
                                            ->label('Produit')
                                            ->options(function (Get $get) {
                                                $bonCommandeId = $get('../../bon_commande_id');

                                                if (! $bonCommandeId) {
                                                    return [];
                                                }

                                                $bonCommande = BonCommande::find($bonCommandeId);

                                                return DetailCommande::query()
                                                    ->where('commande_id', $bonCommande?->commande_id)
                                                    ->with('product')
                                                    ->get()
                                                    ->mapWithKeys(fn (DetailCommande $d) => [
                                                        $d->id => $d->product?->designation . ' (qté cmd: ' . $d->quantite . ')',
                                                    ]);
                                            })
                                            ->searchable()
                                            ->required()
                                            ->live()
                                            ->columnSpan(5)
                                            // Pré-remplit qté facturée = qté commandée, prix unitaire,
                                            // et remet la remise à 0 quand on choisit/change le produit
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $detail = DetailCommande::find($state);

                                                if (! $detail) {
                                                    return;
                                                }

                                                $set('quantite_commande', $detail->quantite);
                                                $set('quantite_facturee', $detail->quantite);
                                                $set('prix_unitaire_ht', $detail->pu_achat_HT);
                                                $set('montant_remise', 0);

                                                self::recalculerLigne($set, $get);
                                            }),

                                        TextInput::make('quantite_commande')
                                            ->label('Qté cmd.')
                                            ->numeric()
                                            ->default(0)
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(2),

                                        TextInput::make('quantite_facturee')
                                            ->label('Qté facturée')
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->columnSpan(3)
                                            ->suffixAction(
                                                Action::make('resetQuantite')
                                                    ->icon('heroicon-m-arrow-path')
                                                    ->tooltip('Reprendre la quantité commandée')
                                                    ->action(function (Set $set, Get $get) {
                                                        $set('quantite_facturee', $get('quantite_commande'));
                                                        self::recalculerLigne($set, $get);
                                                    }),
                                            )
                                            ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculerLigne($set, $get)),

                                        TextInput::make('prix_unitaire_ht')
                                            ->label('PU HT')
                                            ->numeric()
                                            ->required()
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->columnSpan(2)
                                            ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculerLigne($set, $get)),

                                        TextInput::make('montant_remise')
                                            ->label('Remise')
                                            ->numeric()
                                            ->default(0)
                                            ->live(onBlur: true)
                                            ->columnSpan(2)
                                            ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculerLigne($set, $get)),

                                        TextInput::make('montant_final_ht')
                                            ->label('Montant HT')
                                            ->numeric()
                                            ->default(0)
                                            ->disabled()
                                            ->dehydrated()
                                            ->columnSpan(2)
                                            ->extraInputAttributes(['class' => 'font-semibold']),

                                        Hidden::make('montant_ht')->default(0),
                                        Hidden::make('montant_final_net')->default(0),
                                    ]),
                            ])
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculerTotauxFacture($set, $get))
                            ->deleteAction(
                                fn ($action) => $action->after(fn (Set $set, Get $get) => self::recalculerTotauxFacture($set, $get)),
                            )
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['detail_commande_id']
                                ? DetailCommande::find($state['detail_commande_id'])?->product?->designation
                                : 'Nouvelle ligne')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                Section::make('Totaux')
                    ->columns(5)
                    ->schema([
                        TextInput::make('montant_ht')
                            ->label('Montant HT')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('taux_tva')
                            ->label('Taux TVA (%)')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculerTotauxFacture($set, $get)),

                        TextInput::make('montant_tva')
                            ->label('Montant TVA')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),

                        TextInput::make('remise')
                            ->label('Remise globale')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, Get $get) => self::recalculerTotauxFacture($set, $get)),

                        TextInput::make('montant_ttc')
                            ->label('Montant TTC')
                            ->numeric()
                            ->step(0.01)
                            ->default(0)
                            ->disabled()
                            ->dehydrated()
                            ->extraInputAttributes(['class' => 'font-bold text-lg']),
                    ])
                    ->columnSpanFull(),

                Hidden::make('created_by')
                    ->default(fn () => auth()->id())
                    ->dehydrated(),
            ]);
    }

    /**
     * Génère automatiquement les lignes de detailFactures à partir de toutes
     * les detail_commandes liées à la commande du bon de commande sélectionné.
     *
     * IMPORTANT : les lignes sont indexées par UUID (comme le fait Filament en
     * interne pour le Repeater) et non par indice numérique séquentiel. Sans
     * ça, le binding Livewire de chaque champ (wire:model="detailFactures.{clé}.xxx")
     * ne retrouve pas les valeurs injectées et les inputs s'affichent vides
     * malgré des données correctes en arrière-plan.
     */
    protected static function genererLignesDepuisCommande(Set $set, ?int $commandeId): void
    {
        if (! $commandeId) {
            $set('detailFactures', []);
            return;
        }

        $lignes = DetailCommande::query()
            ->where('commande_id', $commandeId)
            ->with('product')
            ->get()
            ->mapWithKeys(function (DetailCommande $detail) {
                $montantHt = round((float) $detail->quantite * (float) $detail->pu_achat_HT, 2);

                return [
                    (string) Str::uuid() => [
                        'detail_commande_id' => $detail->id,
                        'quantite_commande'  => $detail->quantite,
                        'quantite_facturee'  => $detail->quantite,
                        'prix_unitaire_ht'   => $detail->pu_achat_HT,
                        'montant_ht'         => $montantHt,
                        'montant_remise'     => 0,
                        'montant_final_ht'   => $montantHt,
                        'montant_final_net'  => $montantHt,
                    ],
                ];
            })
            ->toArray();

        $set('detailFactures', $lignes);
    }

    /**
     * Recalcule montant_ht / montant_final_ht / montant_final_net d'une ligne
     * à partir de la quantité facturée, du prix unitaire et de la remise.
     */
    protected static function recalculerLigne(Set $set, Get $get): void
    {
        $quantite = (float) $get('quantite_facturee');
        $prixUnitaire = (float) $get('prix_unitaire_ht');
        $remise = (float) $get('montant_remise');

        $montantHt = round($quantite * $prixUnitaire, 2);
        $montantFinalHt = round($montantHt - $remise, 2);

        $set('montant_ht', $montantHt);
        $set('montant_final_ht', $montantFinalHt);
        $set('montant_final_net', $montantFinalHt);
    }

    /**
     * Recalcule les totaux de la facture (HT, TVA, TTC) à partir de la somme
     * des montants finaux HT de toutes les lignes.
     */
    protected static function recalculerTotauxFacture(Set $set, Get $get): void
    {
        $lignes = collect($get('detailFactures') ?? []);
        $montantHt = round($lignes->sum(fn ($ligne) => (float) ($ligne['montant_final_ht'] ?? 0)), 2);

        $tauxTva = (float) $get('taux_tva');
        $remiseGlobale = (float) $get('remise');

        $montantTva = round($montantHt * $tauxTva / 100, 2);
        $montantTtc = round($montantHt + $montantTva - $remiseGlobale, 2);

        $set('montant_ht', $montantHt);
        $set('montant_tva', $montantTva);
        $set('montant_ttc', $montantTtc);
    }
}
