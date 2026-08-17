<x-filament-panels::page>
    <div class="space-y-8">

        {{-- ================= En-tête session ================= --}}
        <div>
            <h1 class="text-2xl font-bold tracking-tight">Point de vente</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ $session->caisse->name }} — {{ $session->caisse->magasin->name }} · Responsable : {{ $session->responsable?->name }}
            </p>
        </div>

        {{-- ================= Scan produit ================= --}}
        <div>
            <label for="scan-input" class="sr-only">Scanner un produit</label>
            <div class="flex items-center gap-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-4 py-3 shadow-xs focus-within:ring-2 focus-within:ring-[var(--primary-500)] focus-within:border-[var(--primary-500)]">
                <x-filament::icon icon="heroicon-o-qr-code" class="w-5 h-5 text-gray-400 shrink-0" />
                <input
                    id="scan-input"
                    type="text"
                    wire:model="scanInput"
                    wire:keydown.enter="scannerProduit"
                    autofocus
                    autocomplete="off"
                    placeholder="Scanner un code-barres ou saisir un code produit, puis Entrée..."
                    class="w-full border-0 focus:ring-0 bg-transparent text-sm placeholder:text-gray-400"
                />
            </div>
        </div>

        {{-- ================= Panier : table native Filament ================= --}}
        <div>
            {{ $this->table }}
        </div>

        {{-- ================= Encaissement ================= --}}
        <x-filament::section>
            <div class="flex items-center justify-between text-sm font-semibold uppercase">
                <span>Total</span>
                <span>{{ number_format($this->total, 2) }} MUR</span>
            </div>

            <div class="mt-4 space-y-2">
                <div class="flex items-center justify-between text-sm font-medium">
                    <span>Espèces reçues</span>
                    <div class="w-28">
                        <x-filament::input
                            type="number"
                            step="0.01"
                            wire:model.live="cashInput"
                            placeholder="0.00"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-6 gap-2">
                    @foreach ([25, 50, 100, 500, 1000, 2000] as $denom)
                        <x-filament::button
                            type="button"
                            wire:click="ajouterEspeces({{ $denom }})"
                            color="gray"
                            size="xs"
                        >
                            +{{ $denom }}
                        </x-filament::button>
                    @endforeach
                </div>

                @if ($this->monnaie > 0)
                    <div class="flex items-center justify-between text-xs">
                        <span>Monnaie à rendre</span>
                        <x-filament::badge color="success">
                            {{ number_format($this->monnaie, 2) }} MUR
                        </x-filament::badge>
                    </div>
                @endif
            </div>

            <x-filament::button
                type="button"
                wire:click="valider"
                color="primary"
                size="lg"
                :disabled="empty($cart)"
                class="w-full justify-center uppercase tracking-wide mt-6"
            >
                Valider la vente
            </x-filament::button>
        </x-filament::section>

    </div>
</x-filament-panels::page>
