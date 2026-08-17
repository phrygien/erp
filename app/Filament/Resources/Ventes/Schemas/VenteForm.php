<?php

namespace App\Filament\Resources\Ventes\Schemas;

use App\Models\CaisseSession;
use App\Models\Magasin;
use App\Models\Product;
use App\Models\StockLot;
use App\Models\Vente;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;

class VenteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([

                    // ================= ÉTAPE 1 : choix de la caisse =================
                    Step::make('Caisse')
                        ->description('Cliquez sur votre session ouverte, ou choisissez une vente en ligne')
                        ->schema([
                            Radio::make('point_de_vente')
                                ->label('Où enregistrer cette vente ?')
                                ->options(fn (): array => self::optionsPointsDeVente())
                                ->descriptions(fn (): array => self::descriptionsPointsDeVente())
                                ->columns(2)
                                ->required()
                                ->live()
                                // Champ purement transitoire : la vraie
                                // donnée est répartie dans canal_vente /
                                // caisse_session_id / magasin_id ci-dessous.
                                ->dehydrated(false)
                                ->afterStateUpdated(function (callable $set, ?string $state) {
                                    if ($state === 'en_ligne') {
                                        $set('canal_vente', Vente::CANAL_EN_LIGNE);
                                        $set('caisse_session_id', null);
                                        $set('magasin_id', null);

                                        return;
                                    }

                                    $sessionId = (int) str_replace('session:', '', (string) $state);
                                    $session = CaisseSession::with('caisse')->find($sessionId);

                                    $set('canal_vente', Vente::CANAL_CAISSE);
                                    $set('caisse_session_id', $sessionId);
                                    $set('magasin_id', $session?->caisse?->magasin_id);
                                }),

                            Hidden::make('canal_vente')
                                ->default(Vente::CANAL_CAISSE)
                                ->required(),

                            Hidden::make('caisse_session_id'),
                        ]),

                    // ================= ÉTAPE 2 : détails de la vente =================
                    Step::make('Vente')
                        ->schema([
                            Placeholder::make('point_de_vente_recap')
                                ->label('Point de vente')
                                ->columnSpanFull()
                                ->content(function (callable $get): string {
                                    if ($get('canal_vente') === Vente::CANAL_EN_LIGNE) {
                                        return 'Vente en ligne';
                                    }

                                    $session = CaisseSession::with('caisse')->find($get('caisse_session_id'));

                                    return $session
                                        ? "Caisse : {$session->caisse->name} — {$session->caisse->magasin->name}"
                                        : '—';
                                }),

                            // Uniquement pour une vente en ligne : la
                            // session de caisse n'ayant pas déjà déduit le
                            // magasin à l'étape 1.
                            Select::make('magasin_id')
                                ->label('Magasin')
                                ->options(fn () => Magasin::query()->where('active', true)->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->visible(fn (callable $get): bool => $get('canal_vente') === Vente::CANAL_EN_LIGNE)
                                ->required(fn (callable $get): bool => $get('canal_vente') === Vente::CANAL_EN_LIGNE),

                            Select::make('product_id')
                                ->label('Produit')
                                ->searchable()
                                ->getSearchResultsUsing(fn (string $search): array => Product::query()
                                    ->where('state', 'active')
                                    ->where(fn ($q) => $q
                                        ->where('designation', 'like', "%{$search}%")
                                        ->orWhere('product_code', 'like', "%{$search}%")
                                        ->orWhere('EAN', 'like', "%{$search}%"))
                                    ->limit(20)
                                    ->get()
                                    ->mapWithKeys(fn (Product $p) => [$p->id => "{$p->product_code} — {$p->designation}"])
                                    ->all())
                                ->getOptionLabelUsing(fn ($value): ?string => Product::find($value)?->designation)
                                ->live()
                                ->required()
                                ->columnSpanFull()
                                // Dès qu'un produit est choisi, on assigne
                                // automatiquement le plus ancien lot
                                // disponible (FIFO) : jamais de choix manuel
                                // de lot par le caissier.
                                ->afterStateUpdated(function (callable $set, $state) {
                                    self::assignerLotFifo($set, $state);
                                }),

                            // Champ technique, jamais montré ni choisi
                            // directement.
                            Hidden::make('stock_lot_id')
                                ->required(),

                            Placeholder::make('lot_info')
                                ->label('Stock disponible')
                                ->columnSpanFull()
                                ->content(function (callable $get): string {
                                    $lot = StockLot::find($get('stock_lot_id'));

                                    if (! $lot) {
                                        return $get('product_id')
                                            ? 'Aucun stock disponible pour ce produit.'
                                            : '—';
                                    }

                                    return "Lot #{$lot->id} · {$lot->quantite_restante} disponible(s) · entré le {$lot->date_entree->format('d/m/Y')}";
                                }),

                            TextInput::make('quantite')
                                ->label('Quantité')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(1)
                                ->maxValue(fn (callable $get): int => StockLot::find($get('stock_lot_id'))?->quantite_restante ?? 0)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (callable $set, callable $get) => self::recalculerTotal($set, $get)),

                            // Champ virtuel : sert uniquement à calculer le
                            // total en direct. Ni prix_unitaire n'existe sur
                            // ventes, ni de prix de vente défini côté
                            // products.
                            TextInput::make('prix_unitaire')
                                ->label('Prix unitaire HT')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->dehydrated(false)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (callable $set, callable $get) => self::recalculerTotal($set, $get)),

                            TextInput::make('montant_total_ht_vente')
                                ->label('Total HT')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->prefix('MUR')
                                ->helperText('Calculé automatiquement (quantité × prix unitaire), modifiable si besoin.'),
                        ]),
                ])
                    ->columnSpanFull()
                    ->skippable(false),
            ]);
    }

    /**
     * Une carte par session de caisse actuellement ouverte, plus une carte
     * "Vente en ligne" toujours disponible en dernier recours.
     */
    private static function optionsPointsDeVente(): array
    {
        $options = CaisseSession::query()
            ->ouvertes()
            ->with('caisse.magasin')
            ->get()
            ->mapWithKeys(fn (CaisseSession $s) => [
                "session:{$s->id}" => "🖥️ {$s->caisse->name} — {$s->caisse->magasin->name}",
            ])
            ->all();

        $options['en_ligne'] = '🌐 Vente en ligne';

        return $options;
    }

    private static function descriptionsPointsDeVente(): array
    {
        $descriptions = CaisseSession::query()
            ->ouvertes()
            ->with('responsable')
            ->get()
            ->mapWithKeys(fn (CaisseSession $s) => [
                "session:{$s->id}" => 'Responsable : '.($s->responsable?->name ?? '—').' · Ouverte depuis '.$s->ouverte_le->diffForHumans(),
            ])
            ->all();

        $descriptions['en_ligne'] = 'Aucune session requise';

        return $descriptions;
    }

    private static function assignerLotFifo(callable $set, ?int $productId): void
    {
        if (! $productId) {
            $set('stock_lot_id', null);

            return;
        }

        $lot = StockLot::disponibles()
            ->pourProduit($productId)
            ->first();

        $set('stock_lot_id', $lot?->id);
    }

    private static function recalculerTotal(callable $set, callable $get): void
    {
        $quantite = (float) ($get('quantite') ?? 0);
        $prixUnitaire = (float) ($get('prix_unitaire') ?? 0);

        $set('montant_total_ht_vente', round($quantite * $prixUnitaire, 2));
    }
}
