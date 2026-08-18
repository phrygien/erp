<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">

        {{-- Colonne principale : ajout d'articles + panier --}}
        <div class="space-y-6 lg:col-span-2">

                <div class="space-y-4">
                    {{-- Scan code-barres : entrée principale, la plus rapide pour un caissier --}}
                    <div>
                        <label class="fi-fo-field-wrp-label mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Scanner un code-barres
                        </label>
                        <x-filament::input.wrapper prefix-icon="heroicon-o-viewfinder-circle">
                            <x-filament::input
                                type="text"
                                autofocus
                                wire:model="scanInput"
                                wire:keydown.enter="scannerProduit"
                                placeholder="Scannez ou saisissez un EAN / code produit puis Entrée"
                            />
                        </x-filament::input.wrapper>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="h-px flex-1 bg-gray-200 dark:bg-white/10"></div>
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-400">ou rechercher</span>
                        <div class="h-px flex-1 bg-gray-200 dark:bg-white/10"></div>
                    </div>

                    {{-- Recherche texte avec suggestions --}}
                    <div class="relative">
                        <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
                            <x-filament::input
                                type="text"
                                wire:model.live.debounce.300ms="searchQuery"
                                placeholder="Rechercher par code, EAN ou désignation..."
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
                                wire:loading.class="opacity-50"
                                wire:target="searchQuery"
                                class="absolute z-10 mt-1.5 w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg dark:border-white/10 dark:bg-gray-800"
                            >
                                @foreach ($this->searchResults as $result)
                                    <button
                                        type="button"
                                        wire:click="selectionnerProduit({{ $result['id'] }})"
                                        class="flex w-full items-center justify-between border-b border-gray-100 px-4 py-2.5 text-left transition hover:bg-gray-50 last:border-b-0 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/5 dark:hover:bg-white/5"
                                        @disabled($result['stock_disponible'] <= 0)
                                    >
                                        <div>
                                            <div class="text-sm font-medium text-gray-950 dark:text-white">{{ $result['designation'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $result['product_code'] }} · {{ $result['EAN'] }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-semibold text-gray-950 dark:text-white">{{ number_format($result['prix'], 2) }} EUR</div>
                                            <div class="text-xs text-gray-500">Stock : {{ $result['stock_disponible'] }}</div>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{ $this->table }}
        </div>

        {{-- Colonne latérale : encaissement, reste visible pendant le scroll du panier --}}
        <div class="lg:sticky lg:top-20">
            <x-filament::section icon="heroicon-o-banknotes" heading="Paiement">
                <div class="space-y-4">
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
                    </div>

                    {{-- Raccourcis de billets/pièces courants (EUR) --}}
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ([50, 100, 200, 500, 1000, 2000] as $montant)
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
                            icon="heroicon-o-check"
                            class="w-full justify-center"
                            wire:click="valider"
                            wire:loading.attr="disabled"
                            wire:target="valider"
                            :disabled="empty($this->cart)"
                        >
                            Valider la vente
                        </x-filament::button>

                        <x-filament::button
                            type="button"
                            color="danger"
                            outlined
                            icon="heroicon-o-x-mark"
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
</x-filament-panels::page>
