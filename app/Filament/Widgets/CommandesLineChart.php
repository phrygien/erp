<?php

namespace App\Filament\Widgets;

use App\Models\Commande;
use Filament\Widgets\ChartWidget;

class CommandesLineChart extends ChartWidget
{
    protected ?string $heading = 'Évolution des commandes';

    protected function getData(): array
    {
        // Nombre de commandes créées par mois sur les 12 derniers mois
        $months = collect(range(11, 0))
            ->map(fn ($monthsAgo) => now()->subMonths($monthsAgo));

        $data = $months->map(fn ($month) => Commande::query()
            ->whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->count());

        $labels = $months->map(fn ($month) => $month->translatedFormat('M Y'));

        return [
            'datasets' => [
                [
                    'label' => 'Commandes',
                    'data' => $data->all(),
                    'borderColor' => '#f43f5e',
                    'backgroundColor' => 'rgba(244, 63, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $labels->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
