<x-filament-panels::page>
    <div
        x-data="{
            showSuggestions: true,
            now: new Date(),
            calcOpen: false,
            calcDisplay: '0',
            calcStored: null,
            calcOperator: null,
            calcWaitingForOperand: false,
            calcInputDigit(digit) {
                if (this.calcWaitingForOperand) {
                    this.calcDisplay = String(digit);
                    this.calcWaitingForOperand = false;
                } else {
                    this.calcDisplay = this.calcDisplay === '0' ? String(digit) : this.calcDisplay + digit;
                }
            },
            calcInputDecimal() {
                if (this.calcWaitingForOperand) {
                    this.calcDisplay = '0.';
                    this.calcWaitingForOperand = false;
                    return;
                }
                if (!this.calcDisplay.includes('.')) {
                    this.calcDisplay += '.';
                }
            },
            calcClear() {
                this.calcDisplay = '0';
                this.calcStored = null;
                this.calcOperator = null;
                this.calcWaitingForOperand = false;
            },
            calcToggleSign() {
                this.calcDisplay = String(parseFloat(this.calcDisplay) * -1);
            },
            calcPerformOperation(nextOperator) {
                const inputValue = parseFloat(this.calcDisplay);
                if (this.calcStored === null) {
                    this.calcStored = inputValue;
                } else if (this.calcOperator) {
                    const currentValue = this.calcStored || 0;
                    let result = currentValue;
                    switch (this.calcOperator) {
                        case '+': result = currentValue + inputValue; break;
                        case '-': result = currentValue - inputValue; break;
                        case '×': result = currentValue * inputValue; break;
                        case '÷': result = inputValue !== 0 ? currentValue / inputValue : 0; break;
                    }
                    this.calcStored = Math.round(result * 100) / 100;
                    this.calcDisplay = String(this.calcStored);
                }
                this.calcWaitingForOperand = true;
                this.calcOperator = nextOperator;
            },
            calcEquals() {
                this.calcPerformOperation(null);
                this.calcStored = null;
                this.calcOperator = null;
            }
        }"
        x-init="setInterval(() => { now = new Date() }, 1000)"
        @keydown.escape.window="showSuggestions = false; calcOpen = false"
        class="space-y-6"
    >

        {{-- Barre supérieure : horloge en direct + accès rapide à la calculatrice --}}
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-2.5 dark:border-white/10 dark:bg-gray-800">
            <div class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5 text-gray-400" />
                <span x-text="now.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })" class="capitalize"></span>
                <span class="text-gray-300 dark:text-white/20">·</span>
                <span x-text="now.toLocaleTimeString('fr-FR')" class="font-semibold tabular-nums text-gray-950 dark:text-white"></span>
            </div>

            <x-filament::button
                type="button"
                size="sm"
                color="gray"
                outlined
                icon="heroicon-o-calculator"
                wire:click.prevent="$dispatch('noop')"
                x-on:click="calcOpen = true"
            >
                Calculatrice
            </x-filament::button>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">

            {{-- Colonne principale : ajout d'articles + panier --}}
            <div class="space-y-6 lg:col-span-2">

                <div class="space-y-4">
                    <x-filament::fieldset>
                        <x-slot name="label">
                            Scanner un code-barres
                        </x-slot>

                        <div class="space-y-2">
                            {{-- Scan physique (douchette USB en mode "clavier") : entrée
                                 principale, la plus rapide pour un caissier équipé. --}}
                            <div class="relative">
                                <x-filament::input.wrapper prefix-icon="heroicon-o-viewfinder-circle">
                                    <x-filament::input
                                        type="text"
                                        autofocus
                                        wire:model="scanInput"
                                        wire:keydown.enter="scannerProduit"
                                        wire:loading.attr="disabled"
                                        wire:target="scannerProduit"
                                        placeholder="Scannez ou saisissez un EAN / code produit puis Entrée"
                                    />
                                </x-filament::input.wrapper>

                                <div
                                    wire:loading
                                    wire:target="scannerProduit"
                                    class="pointer-events-none absolute inset-y-0 right-3 flex items-center"
                                >
                                    <x-filament::loading-indicator class="h-5 w-5 text-gray-400" />
                                </div>
                            </div>

                            <div class="flex items-center gap-3 text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                <span class="h-px flex-1 bg-gray-200 dark:bg-white/10"></span>
                                ou
                                <span class="h-px flex-1 bg-gray-200 dark:bg-white/10"></span>
                            </div>

                            {{-- Scan caméra (filament-barcode-scanner-field) : ouvre un
                                 modal utilisant l'appareil photo, utile sur mobile/tablette
                                 ou en l'absence de douchette physique. --}}
                            {{ $this->scannerForm }}
                        </div>
                    </x-filament::fieldset>

                    <x-filament::fieldset>
                        <x-slot name="label">
                            Rechercher manuellement
                        </x-slot>

                        {{-- Recherche texte avec suggestions --}}
                        <div class="relative" x-on:click.outside="showSuggestions = false">
                            <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
                                <x-filament::input
                                    type="text"
                                    wire:model.live.debounce.300ms="searchQuery"
                                    x-on:focus="showSuggestions = true"
                                    placeholder="Rechercher par code, EAN ou désignation..."
                                    role="combobox"
                                    aria-expanded="true"
                                    aria-controls="pos-search-results"
                                />
                            </x-filament::input.wrapper>

                            {{-- Spinner affiché pendant le calcul de searchResults (debounce + requête serveur) --}}
                            <div
                                wire:loading
                                wire:target="searchQuery"
                                class="pointer-events-none absolute inset-y-0 right-3 flex items-center"
                            >
                                <x-filament::loading-indicator class="h-5 w-5 text-gray-400" />
                            </div>

                            @if (! empty($this->searchResults))
                                <div
                                    x-show="showSuggestions"
                                    x-cloak
                                    wire:loading.class="opacity-50"
                                    wire:target="searchQuery"
                                    id="pos-search-results"
                                    role="listbox"
                                    class="absolute z-10 mt-1.5 max-h-80 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg dark:border-white/10 dark:bg-gray-800"
                                >
                                    @foreach ($this->searchResults as $result)
                                        <button
                                            type="button"
                                            role="option"
                                            wire:click="selectionnerProduit({{ $result['id'] }})"
                                            wire:key="search-result-{{ $result['id'] }}"
                                            x-on:click="showSuggestions = false"
                                            class="flex w-full items-center justify-between border-b border-gray-100 px-4 py-2.5 text-left transition hover:bg-gray-50 last:border-b-0 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/5 dark:hover:bg-white/5"
                                            @disabled($result['stock_disponible'] <= 0)
                                        >
                                            <div>
                                                <div class="text-sm font-medium text-gray-950 dark:text-white">{{ $result['designation'] }}</div>
                                                <div class="text-xs text-gray-500">{{ $result['product_code'] }} · {{ $result['EAN'] }}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-semibold text-gray-950 dark:text-white">{{ number_format($result['prix'], 2) }} EUR</div>
                                                <div class="text-xs {{ $result['stock_disponible'] <= 0 ? 'text-danger-500 font-medium' : 'text-gray-500' }}">
                                                    {{ $result['stock_disponible'] <= 0 ? 'Rupture de stock' : 'Stock : ' . $result['stock_disponible'] }}
                                                </div>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </x-filament::fieldset>
                </div>

                @if (empty($this->cart))
                    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 py-12 text-center dark:border-white/10">
                        <x-filament::icon icon="heroicon-o-shopping-cart" class="h-10 w-10 text-gray-300 dark:text-gray-600" />
                        <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">Le panier est vide</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Scannez un article ou recherchez-le ci-dessus</p>
                    </div>
                @else
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Articles du panier</h3>
                        <x-filament::button
                            type="button"
                            color="danger"
                            size="sm"
                            wire:click="viderPanier"
                            wire:confirm="Vider le panier ?"
                        >
                            Reset
                        </x-filament::button>
                    </div>

                    {{ $this->cartForm }}
                @endif
            </div>

            {{-- Colonne latérale : encaissement, reste visible pendant le scroll du panier --}}
            <div class="lg:sticky lg:top-20">
                <x-filament::section icon="heroicon-o-banknotes" heading="Paiement">
                    <div class="space-y-4">
                        {{-- Total mis en évidence : c'est l'info la plus importante pour le caissier --}}
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 dark:bg-white/5">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Total à payer</span>
                            <span class="text-xl font-bold text-gray-950 dark:text-white">{{ number_format($this->total, 2) }} EUR</span>
                        </div>

                        {{ $this->venteInfolist }}

                        <div class="h-px bg-gray-200 dark:bg-white/10"></div>

                        <div>
                            <label class="fi-fo-field-wrp-label mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Espèces reçues
                            </label>
                            <x-filament::input.wrapper suffix="EUR">
                                <x-filament::input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    wire:model.live="cashInput"
                                    placeholder="0.00"
                                />
                            </x-filament::input.wrapper>

                            {{-- Monnaie à rendre : calcul indispensable pour un caissier --}}
                            @if ($this->cashInput !== null && $this->cashInput !== '' && (float) $this->cashInput >= $this->total)
                                <p class="mt-1.5 text-sm text-success-600 dark:text-success-400">
                                    Monnaie à rendre : <span class="font-semibold">{{ number_format((float) $this->cashInput - $this->total, 2) }} EUR</span>
                                </p>
                            @elseif ($this->cashInput !== null && $this->cashInput !== '')
                                <p class="mt-1.5 text-sm text-danger-600 dark:text-danger-400">
                                    Manque {{ number_format($this->total - (float) $this->cashInput, 2) }} EUR
                                </p>
                            @endif
                        </div>

                        {{-- Raccourcis de billets/pièces courants (EUR) --}}
                        <div class="grid grid-cols-3 gap-2">
                            @foreach ([25, 50, 100, 500, 1000, 2000] as $montant)
                                <x-filament::button
                                    type="button"
                                    size="sm"
                                    color="gray"
                                    outlined
                                    wire:click="ajouterEspeces({{ $montant }})"
                                >
                                    +{{ $montant }}
                                </x-filament::button>
                            @endforeach
                        </div>

                        <div class="flex gap-2">
                            <x-filament::button
                                type="button"
                                size="sm"
                                color="gray"
                                outlined
                                class="flex-1"
                                wire:click="$set('cashInput', '{{ $this->total }}')"
                            >
                                Montant exact
                            </x-filament::button>

                            <x-filament::button
                                type="button"
                                size="sm"
                                color="gray"
                                outlined
                                wire:click="$set('cashInput', '')"
                            >
                                Effacer
                            </x-filament::button>
                        </div>

                        <div class="h-px bg-gray-200 dark:bg-white/10"></div>

                        <div class="space-y-2">
                            <x-filament::button
                                type="button"
                                color="success"
                                class="w-full justify-center"
                                wire:click="valider"
                                wire:loading.attr="disabled"
                                wire:target="valider"
                                :disabled="empty($this->cart) || (float) ($this->cashInput ?? 0) < $this->total"
                            >
                                Valider la vente
                            </x-filament::button>

                            <x-filament::button
                                type="button"
                                color="danger"
                                class="w-full justify-center"
                                wire:click="viderPanier"
                                wire:confirm="Annuler la vente en cours et vider le panier ?"
                                :disabled="empty($this->cart)"
                            >
                                Annuler la vente
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        </div>

        {{-- Calculatrice flottante : utile pour un caissier (rendu de monnaie, remises, etc.) --}}
        <div
            x-show="calcOpen"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4"
            x-on:click.self="calcOpen = false"
        >
            <div
                x-show="calcOpen"
                x-transition
                x-on:click.outside="calcOpen = false"
                class="w-full max-w-xs overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-gray-800"
            >
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-white/10">
                    <span class="text-sm font-semibold text-gray-950 dark:text-white">Calculatrice</span>
                    <button
                        type="button"
                        x-on:click="calcOpen = false"
                        class="text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200"
                    >
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <div class="px-4 py-3">
                    <div
                        class="mb-3 truncate rounded-xl bg-gray-50 px-3 py-3 text-right text-2xl font-semibold tabular-nums text-gray-950 dark:bg-white/5 dark:text-white"
                        x-text="calcDisplay"
                    ></div>

                    <div class="grid grid-cols-4 gap-2">
                        <x-filament::button type="button" color="gray" outlined x-on:click="calcClear()">C</x-filament::button>
                        <x-filament::button type="button" color="gray" outlined x-on:click="calcToggleSign()">±</x-filament::button>
                        <x-filament::button type="button" color="gray" outlined x-on:click="calcPerformOperation('÷')">÷</x-filament::button>
                        <x-filament::button type="button" color="warning" x-on:click="calcPerformOperation('×')">×</x-filament::button>

                        <x-filament::button type="button" color="gray" outlined x-on:click="calcInputDigit(7)">7</x-filament::button>
                        <x-filament::button type="button" color="gray" outlined x-on:click="calcInputDigit(8)">8</x-filament::button>
                        <x-filament::button type="button" color="gray" outlined x-on:click="calcInputDigit(9)">9</x-filament::button>
                        <x-filament::button type="button" color="warning" x-on:click="calcPerformOperation('-')">−</x-filament::button>

                        <x-filament::button type="button" color="gray" outlined x-on:click="calcInputDigit(4)">4</x-filament::button>
                        <x-filament::button type="button" color="gray" outlined x-on:click="calcInputDigit(5)">5</x-filament::button>
                        <x-filament::button type="button" color="gray" outlined x-on:click="calcInputDigit(6)">6</x-filament::button>
                        <x-filament::button type="button" color="warning" x-on:click="calcPerformOperation('+')">+</x-filament::button>

                        <x-filament::button type="button" color="gray" outlined x-on:click="calcInputDigit(1)">1</x-filament::button>
                        <x-filament::button type="button" color="gray" outlined x-on:click="calcInputDigit(2)">2</x-filament::button>
                        <x-filament::button type="button" color="gray" outlined x-on:click="calcInputDigit(3)">3</x-filament::button>
                        <x-filament::button type="button" color="success" class="row-span-2" x-on:click="calcEquals()">=</x-filament::button>

                        <x-filament::button type="button" color="gray" outlined class="col-span-2" x-on:click="calcInputDigit(0)">0</x-filament::button>
                        <x-filament::button type="button" color="gray" outlined x-on:click="calcInputDecimal()">.</x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
