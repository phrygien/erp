<?php

namespace App\Filament\Pages;

use App\Models\CaisseSession;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockLot;
use App\Models\StockMouvement;
use App\Models\Vente;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PosCaisse extends Page implements HasTable, HasSchemas
{
    use InteractsWithTable;
    use InteractsWithSchemas;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.pos-caisse';

    //protected Width|string|null $maxContentWidth = Width::Full;

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
     * @var array<int, array{product_id:int, product_code:string, designation:string, prix:float, quantite:int, stock_lot_id:int, max_quantite:int}>
     */
    public array $cart = [];

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
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): Collection => collect($this->cart)
                ->values()
                ->map(fn (array $item) => [
                    'id' => $item['product_id'],
                    ...$item,
                ]))
            ->columns([
                TextColumn::make('designation')
                    ->label('Produit')
                    ->weight('medium')
                    ->description(fn ($record) => $record['product_code']),

                TextColumn::make('prix')
                    ->label('Prix unitaire')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' EUR'),

                TextColumn::make('quantite')
                    ->label('Quantité')
                    ->alignCenter(),

                TextColumn::make('total')
                    ->label('Total')
                    ->state(fn ($record) => $record['prix'] * $record['quantite'])
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' EUR')
                    ->weight('semibold')
                    ->alignEnd(),
            ])
            ->recordActions([
                Action::make('decrementer')
                    ->label('')
                    ->icon('heroicon-o-minus')
                    ->size('sm')
                    ->color('gray')
                    ->action(fn ($record) => $this->decrementer($record['product_id'])),

                Action::make('incrementer')
                    ->label('')
                    ->icon('heroicon-o-plus')
                    ->size('sm')
                    ->color('gray')
                    ->action(fn ($record) => $this->incrementer($record['product_id'])),

                Action::make('retirer')
                    ->label('')
                    ->icon('heroicon-o-x-mark')
                    ->size('sm')
                    ->color('danger')
                    ->action(fn ($record) => $this->retirerDuPanier($record['product_id'])),
            ])
            ->emptyStateHeading('Aucun article scanné')
            ->emptyStateDescription('Scannez un code-barres ou recherchez un produit pour commencer la vente.')
            ->emptyStateIcon('heroicon-o-shopping-cart')
            ->paginated(false);
    }

    /**
     * Infolist (v5 : Schema unifié) récapitulatif de la vente en cours :
     * total, espèces reçues et monnaie à rendre. Chaque TextEntry recalcule
     * son état via une closure, donc pas besoin de ->record() / ->constantState()
     * ici. Référencé dans la vue via {{ $this->venteInfolist }}.
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
     * Retourne un tableau simple (pas de modèles Eloquent) pour rester
     * léger côté Livewire wire:snapshot.
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
                $this->synchroniserCashInputAvecTotal();
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

        $this->synchroniserCashInputAvecTotal();
    }

    public function incrementer(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        if ($this->cart[$productId]['quantite'] < $this->cart[$productId]['max_quantite']) {
            $this->cart[$productId]['quantite']++;
            $this->synchroniserCashInputAvecTotal();
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

        $this->synchroniserCashInputAvecTotal();
    }

    public function retirerDuPanier(int $productId): void
    {
        unset($this->cart[$productId]);

        $this->synchroniserCashInputAvecTotal();
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

    public function viderPanier(): void
    {
        $this->cart = [];
        $this->cashInput = '';
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
