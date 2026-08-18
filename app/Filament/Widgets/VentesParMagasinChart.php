<?php

namespace App\Filament\Widgets;

use App\Models\Magasin;
use App\Models\Vente;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class VentesParMagasinChart extends ChartWidget
{
    protected ?string $heading = 'Ventes par magasin';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public ?string $filter = 'month';

    /**
     * Palette cyclique appliquée aux magasins, dans l'ordre de leur id.
     * Au-delà de 8 magasins, la couleur se répète.
     */
    protected const COULEURS = [
        '#36A2EB', '#FF9F40', '#4BC0C0', '#9966FF',
        '#FF6384', '#C9CB3F', '#8B5CF6', '#22C55E',
    ];

    protected function getFilters(): ?array
    {
        return [
            'week' => '7 derniers jours',
            'month' => '30 derniers jours',
            'year' => 'Cette année',
        ];
    }

    protected function getData(): array
    {
        [$start, $groupFormat, $labels] = $this->getPeriodConfig();

        $magasins = Magasin::query()->where('active', true)->orderBy('name')->get();

        $ventes = Vente::query()
            ->whereNotNull('magasin_id')
            ->where('created_at', '>=', $start)
            ->get()
            ->groupBy('magasin_id');

        $datasets = $magasins->values()->map(function (Magasin $magasin, int $index) use ($ventes, $groupFormat, $labels) {
            $data = $this->aggregateByPeriod(
                $ventes->get($magasin->id, collect()),
                $groupFormat,
                $labels
            );

            $couleur = self::COULEURS[$index % count(self::COULEURS)];

            return [
                'label' => $magasin->name,
                'data' => array_values($data),
                'borderColor' => $couleur,
                'backgroundColor' => $couleur . '33',
            ];
        })->all();

        return [
            'datasets' => $datasets,
            'labels' => array_keys($labels),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getPeriodConfig(): array
    {
        return match ($this->filter) {
            'week' => [
                now()->subDays(6)->startOfDay(),
                'Y-m-d',
                collect(range(6, 0))
                    ->mapWithKeys(fn ($i) => [now()->subDays($i)->format('d/m') => 0])
                    ->all(),
            ],
            'year' => [
                now()->startOfYear(),
                'Y-m',
                collect(range(0, now()->month - 1))
                    ->mapWithKeys(fn ($i) => [now()->startOfYear()->addMonths($i)->translatedFormat('M') => 0])
                    ->all(),
            ],
            default => [
                now()->subDays(29)->startOfDay(),
                'Y-m-d',
                collect(range(29, 0))
                    ->mapWithKeys(fn ($i) => [now()->subDays($i)->format('d/m') => 0])
                    ->all(),
            ],
        };
    }

    protected function aggregateByPeriod($ventes, string $groupFormat, array $labels): array
    {
        $grouped = $ventes->groupBy(
            fn (Vente $vente) => Carbon::parse($vente->created_at)->format($groupFormat)
        )->map(
            fn ($group) => (float) $group->sum('montant_total_ht_vente')
        );

        $result = [];
        $index = 0;
        $total = count($labels);

        foreach ($labels as $label => $default) {
            $key = $groupFormat === 'Y-m'
                ? now()->startOfYear()->addMonths($index)->format('Y-m')
                : now()->subDays($total - 1 - $index)->format('Y-m-d');

            $result[$label] = $grouped->get($key, $default);
            $index++;
        }

        return $result;
    }
}
