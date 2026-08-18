<?php

namespace App\Filament\Pages;

use App\Models\CaisseSession;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockLot;
use App\Models\StockMouvement;
use App\Models\Vente;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class PosCaisse extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.pos-caisse';

    public int $caisseSessionId;

    public ?CaisseSession $session = null;

    public string $scanInput = '';

    /**
     * Requête de recherche produit (code, EAN ou désignation).
     */
    public string $searchQuery = '';

    /**
     * Nombre max de résultats affichés dans la liste de recherche.
     */
    protected int $searchLimit = 8;

    /**
     * Source de vérité du panier, keyée par product_id. Le Repeater
     * (cartForm) en est une représentation éditable, resynchronisée dans
     * les deux sens via syncCartForm() / syncCartFromRepeaterState().
     *
     * @var array<int, array{product_id:int, product_code:string, designation:string, prix:float, quantite:int, stock_lot_id:int, max_quantite:int}>
     */
    public array $cart = [];

    /**
     * State du schema Repeater. Ne pas manipuler directement : passer par
     * $this->cart puis syncCartForm().
     */
    public array $cartData = [];

    public string $cashInput = '';

    public function mount(?int $caisseSessionId = null): void
    {
        // Filament résout mount() via les paramètres de route, pas la query
        // string. Le lien généré par CaisseSessionsTable::getUrl() passe
        // caisseSessionId en query string (?caisseSessionId=2), donc on le
        // récupère manuellement ici si l'argument de route est absent.
        $caisseSessionId ??= (int) request()->query('caisseSessionId');

        if (! $caisseSessionId) {
            abort(404);
        }

        $this->caisseSessionId = $caisseSessionId;
        $this->session = CaisseSession::with('caisse.magasin', 'responsable')->findOrFail($caisseSessionId);

        if (! $this->session->estOuverte()) {
            Notification::make()
                ->title('Cette session de caisse est déjà fermée.')
                ->danger()
                ->send();

            $this->redirect(route('filament.admin.resources.caisse-sessions.index'));

            return;
        }

        $this->cartForm->fill(['cart' => []]);
    }

    /**
     * Repeater en layout table (Filament v4) : chaque ligne du panier est
     * éditable (produit, quantité), avec drag-to-reorder et suppression.
     * Référencé dans la vue via {{ $this->cartForm }}.
     */
    public function cartForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('cartData')
            ->components([
                Repeater::make('cart')
                    ->hiddenLabel()
                    ->table([
                        TableColumn::make('Produit'),
                        TableColumn::make('Quantité')->width('110px'),
                        TableColumn::make('Prix unitaire')->width('140px'),
                    ])
                    ->schema([
                        Select::make('product_id')
                            ->label('Produit')
                            ->searchable()
                            ->options(fn () => Product::query()
                                ->where('state', 'active')
                                ->limit(50)
                                ->pluck('designation', 'id'))
                            ->getSearchResultsUsing(fn (string $search) => Product::query()
                                ->where('state', 'active')
                                ->where(fn ($q) => $q
                                    ->where('designation', 'like', "%{$search}%")
                                    ->orWhere('product_code', 'like', "%{$search}%")
                                    ->orWhere('EAN', 'like', "%{$search}%"))
                                ->limit(20)
                                ->pluck('designation', 'id'))
                            ->getOptionLabelUsing(fn ($value) => Product::find($value)?->designation)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! $state) {
                                    return;
                                }

                                $product = Product::find($state);
                                $lot = StockLot::disponibles()->pourProduit($state)->first();

                                if (! $product || ! $lot) {
                                    Notification::make()
                                        ->title('Aucun stock disponible pour ce produit.')
                                        ->warning()
                                        ->send();
                                }

                                $set('product_code', $product?->product_code);
                                $set('prix', $product ? (float) $product->pght_parkod : null);
                                $set('stock_lot_id', $lot?->id);
                                $set('max_quantite', $lot?->quantite_restante ?? 0);
                                $set('quantite', 1);
                            }),

                        TextInput::make('quantite')
                            ->label('Quantité')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $max = (int) ($get('max_quantite') ?? 0);

                                if ($max > 0 && (int) $state > $max) {
                                    $set('quantite', $max);

                                    Notification::make()
                                        ->title("Quantité limitée au stock disponible ({$max}).")
                                        ->warning()
                                        ->send();
                                }
                            }),

                        TextInput::make('prix')
                            ->label('Prix unitaire')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),

                        Hidden::make('product_code'),
                        Hidden::make('stock_lot_id'),
                        Hidden::make('max_quantite'),
                    ])
                    ->addActionLabel('Ajouter un article')
                    ->reorderable()
                    ->deletable()
                    ->columnSpanFull()
                    ->live()
                    ->afterStateUpdated(fn (?array $state) => $this->syncCartFromRepeaterState($state)),
            ]);
    }

    /**
     * Infolist (v5 : Schema unifié) récapitulatif de la vente en cours :
     * total, espèces reçues et monnaie à rendre. Chaque TextEntry recalcule
     * son état via une closure. Référencé dans la vue via {{ $this->venteInfolist }}.
     */
    public function venteInfolist(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                TextEntry::make('total')
                    ->label('Total à payer')
                    ->state(fn () => $this->total)
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' EUR')
                    ->weight('bold')
                    ->size('lg'),

                TextEntry::make('especes')
                    ->label('Espèces reçues')
                    ->state(fn () => (float) ($this->cashInput ?: 0))
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' EUR'),

                TextEntry::make('monnaie')
                    ->label('Monnaie à rendre')
                    ->state(fn () => $this->monnaie)
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' EUR')
                    ->weight('bold')
                    ->size('lg')
                    ->color(fn () => $this->monnaie > 0 ? 'success' : 'gray'),
            ]);
    }

    public function getTotalProperty(): float
    {
        return collect($this->cart)->sum(fn ($item) => $item['prix'] * $item['quantite']);
    }

    public function getMonnaieProperty(): float
    {
        $cash = (float) ($this->cashInput ?: 0);

        return max(0, $cash - $this->total);
    }

    /**
     * Résultats de recherche produit (code produit, EAN ou désignation),
     * calculés à chaque frappe via wire:model.live sur searchQuery.
     *
     * @return array<int, array{id:int, product_code:string, EAN:?string, designation:string, prix:float, stock_disponible:int}>
     */
    public function getSearchResultsProperty(): array
    {
        $term = trim($this->searchQuery);

        if ($term === '' || mb_strlen($term) < 2) {
            return [];
        }

        return Product::query()
            ->where('state', 'active')
            ->where(function ($q) use ($term) {
                $q->where('product_code', 'like', "%{$term}%")
                    ->orWhere('EAN', 'like', "%{$term}%")
                    ->orWhere('designation', 'like', "%{$term}%");
            })
            ->limit($this->searchLimit)
            ->get()
            ->map(function (Product $product) {
                $stockDisponible = StockLot::disponibles()
                    ->pourProduit($product->id)
                    ->sum('quantite_restante');

                return [
                    'id' => $product->id,
                    'product_code' => $product->product_code,
                    'EAN' => $product->EAN,
                    'designation' => $product->designation,
                    'prix' => (float) $product->pght_parkod,
                    'stock_disponible' => (int) $stockDisponible,
                ];
            })
            ->all();
    }

    /**
     * Appelé quand l'utilisateur scanne un code-barres (EAN) ou saisit un
     * product_code dans le champ de scan, puis valide (Enter). Le produit
     * correspondant est ajouté directement comme nouvelle ligne du panier.
     */
    public function scannerProduit(): void
    {
        $code = trim($this->scanInput);

        if ($code === '') {
            return;
        }

        $product = Product::query()
            ->where('state', 'active')
            ->where(fn ($q) => $q
                ->where('EAN', $code)
                ->orWhere('product_code', $code))
            ->first();

        $this->scanInput = '';

        if (! $product) {
            Notification::make()
                ->title("Aucun produit trouvé pour le code « {$code} ».")
                ->warning()
                ->send();

            return;
        }

        $this->ajouterAuPanier($product->id);
    }

    /**
     * Hook de cycle de vie Livewire : appelé automatiquement à chaque mise
     * à jour de $scanInput (via wire:model.live sur le champ de scan).
     * Permet d'ajouter le produit dès la fin du scan, sans attendre que le
     * scanner (ou l'utilisateur) envoie un caractère "Entrée".
     */
    public function updatedScanInput(): void
    {
        if (trim($this->scanInput) === '') {
            return;
        }

        $this->scannerProduit();
    }

    /**
     * Appelé au clic sur un résultat de la recherche (code, EAN ou
     * désignation). Réutilise ajouterAuPanier() puis vide la recherche.
     */
    public function selectionnerProduit(int $productId): void
    {
        $this->ajouterAuPanier($productId);
        $this->searchQuery = '';
    }

    public function ajouterAuPanier(int $productId): void
    {
        // Rien à faire si déjà au max disponible dans le lot FIFO courant :
        // cette limitation simple (une Vente = un seul lot) empêche de
        // vendre plus que ce que le plus ancien lot contient.
        $lot = StockLot::disponibles()->pourProduit($productId)->first();

        if (! $lot) {
            Notification::make()->title('Aucun stock disponible pour ce produit.')->warning()->send();

            return;
        }

        if (isset($this->cart[$productId])) {
            if ($this->cart[$productId]['quantite'] < $this->cart[$productId]['max_quantite']) {
                $this->cart[$productId]['quantite']++;
                $this->apresMutationPanier();
            }

            return;
        }

        $product = Product::findOrFail($productId);

        $this->cart[$productId] = [
            'product_id' => $product->id,
            'product_code' => $product->product_code,
            'designation' => $product->designation,
            'prix' => (float) $product->pght_parkod,
            'quantite' => 1,
            'stock_lot_id' => $lot->id,
            'max_quantite' => $lot->quantite_restante,
        ];

        $this->apresMutationPanier();
    }

    public function incrementer(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        if ($this->cart[$productId]['quantite'] < $this->cart[$productId]['max_quantite']) {
            $this->cart[$productId]['quantite']++;
            $this->apresMutationPanier();
        }
    }

    public function decrementer(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        $this->cart[$productId]['quantite']--;

        if ($this->cart[$productId]['quantite'] <= 0) {
            unset($this->cart[$productId]);
        }

        $this->apresMutationPanier();
    }

    public function retirerDuPanier(int $productId): void
    {
        unset($this->cart[$productId]);

        $this->apresMutationPanier();
    }

    public function ajouterEspeces(int $montant): void
    {
        $this->cashInput = (string) ((float) ($this->cashInput ?: 0) + $montant);
    }

    /**
     * Remplace automatiquement le montant en espèces par le total courant
     * du panier, à chaque ajout/retrait d'article. Évite d'avoir à cliquer
     * sur un bouton "Montant exact" : le caissier n'a plus qu'à corriger
     * la valeur si le client règle avec un billet différent.
     */
    protected function synchroniserCashInputAvecTotal(): void
    {
        $this->cashInput = $this->total > 0 ? (string) $this->total : '';
    }

    /**
     * À appeler après toute mutation de $this->cart faite en dehors du
     * Repeater (scan, recherche, boutons +/-) : recalcule les espèces et
     * repousse l'état vers le Repeater pour qu'il reste synchronisé.
     */
    protected function apresMutationPanier(): void
    {
        $this->synchroniserCashInputAvecTotal();
        $this->syncCartForm();
    }

    /**
     * Pousse $this->cart (source de vérité) vers le state du Repeater.
     */
    protected function syncCartForm(): void
    {
        $this->cartForm->fill([
            'cart' => array_values($this->cart),
        ]);
    }

    /**
     * Rappelée quand l'utilisateur édite directement une ligne du
     * Repeater (changement de produit, quantité, réordonnancement,
     * suppression) : redescend l'état vers $this->cart, en clampant les
     * quantités au stock disponible du lot sélectionné.
     */
    protected function syncCartFromRepeaterState(?array $state): void
    {
        $newCart = [];

        foreach ($state ?? [] as $row) {
            $productId = $row['product_id'] ?? null;

            if (! $productId) {
                continue;
            }

            $maxQuantite = (int) ($row['max_quantite'] ?? 0);
            $quantite = max(1, (int) ($row['quantite'] ?? 1));

            if ($maxQuantite > 0) {
                $quantite = min($quantite, $maxQuantite);
            }

            // Si le même produit apparaît sur plusieurs lignes (édition
            // manuelle), on cumule les quantités plutôt que d'écraser.
            if (isset($newCart[$productId])) {
                $newCart[$productId]['quantite'] += $quantite;

                continue;
            }

            $product = Product::find($productId);

            $newCart[$productId] = [
                'product_id' => $productId,
                'product_code' => $row['product_code'] ?? $product?->product_code ?? '',
                'designation' => $product?->designation ?? '',
                'prix' => (float) ($row['prix'] ?? $product?->pght_parkod ?? 0),
                'quantite' => $quantite,
                'stock_lot_id' => $row['stock_lot_id'] ?? null,
                'max_quantite' => $maxQuantite,
            ];
        }

        $this->cart = $newCart;
        $this->synchroniserCashInputAvecTotal();
    }

    public function viderPanier(): void
    {
        $this->cart = [];
        $this->cashInput = '';
        $this->syncCartForm();
    }

    public function valider(): void
    {
        if (empty($this->cart)) {
            Notification::make()->title('Le panier est vide.')->warning()->send();

            return;
        }

        $cash = (float) ($this->cashInput ?: 0);

        if ($cash < $this->total) {
            Notification::make()->title('Montant en espèces insuffisant.')->danger()->send();

            return;
        }

        try {
            DB::transaction(function () {
                foreach ($this->cart as $item) {
                    $lot = StockLot::lockForUpdate()->findOrFail($item['stock_lot_id']);

                    if ($lot->quantite_restante < $item['quantite']) {
                        throw new \RuntimeException("Stock insuffisant pour {$item['designation']} (quelqu'un d'autre a peut-être vendu ce lot entre-temps).");
                    }

                    $vente = Vente::create([
                        'product_id' => $item['product_id'],
                        'magasin_id' => $this->session->caisse->magasin_id,
                        'stock_lot_id' => $lot->id,
                        'canal_vente' => Vente::CANAL_CAISSE,
                        'caisse_session_id' => $this->session->id,
                        'quantite' => $item['quantite'],
                        'montant_total_ht_vente' => round($item['prix'] * $item['quantite'], 2),
                    ]);

                    $stock = Stock::where('product_id', $item['product_id'])->lockForUpdate()->firstOrFail();

                    StockMouvement::enregistrerSortie(
                        stock: $stock,
                        stockLot: $lot,
                        quantite: $item['quantite'],
                        commentaire: "Vente #{$vente->id} — session {$this->session->id}",
                    );
                }
            });
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Échec de la vente')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Vente enregistrée')
            ->body('Monnaie à rendre : ' . number_format($this->monnaie, 2) . ' EUR')
            ->success()
            ->send();

        $this->viderPanier();
    }
}
