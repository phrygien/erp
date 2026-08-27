<div class="flex items-center gap-x-2">
    @foreach ($locales as $locale)
        <button
            wire:click="setLocale('{{ $locale }}')"
            @class([
                'px-2 py-1 text-xs font-medium rounded-md uppercase',
                'bg-primary-600 text-white' => $current === $locale,
                'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800' => $current !== $locale,
            ])
        >
            {{ $locale }}
        </button>
    @endforeach
</div>
