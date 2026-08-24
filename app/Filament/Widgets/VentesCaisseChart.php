<?php

namespace App\Filament\Widgets;

use App\Models\Vente;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class VentesCaisseChart extends ChartWidget
{
    protected ?string $heading = 'Ventes en caisse';

    protected string $color = 'warning';

    public ?string $filter = 'month';
    protected static ?int $sort = 2;
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

        $ventes = Vente::query()
            ->enCaisse()
            ->where('created_at', '>=', $start)
            ->get();

        $data = $this->aggregateByPeriod($ventes, $groupFormat, $labels);

        return [
            'datasets' => [
                [
                    'label' => 'Caisse',
                    'data' => array_values($data),
                    'borderColor' => '#FF9F40',
                    'backgroundColor' => '#FF9F4033',
                    'tension' => 0.4,
                    'fill' => false,
                    'pointRadius' => 2,
                ],
            ],
            'labels' => array_keys($labels),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'elements' => [
                'line' => [
                    'tension' => 0.4,
                ],
            ],
        ];
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
            fn ($group) => (float) $group->sum('montant_total')
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
