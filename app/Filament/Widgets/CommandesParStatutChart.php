<?php

namespace App\Filament\Widgets;

use App\Models\Commande;
use Filament\Widgets\ChartWidget;

class CommandesParStatutChart extends ChartWidget
{
    protected ?string $heading = 'Commandes par statut';

    protected function getData(): array
    {
        $statuts = [
            'cree' => 'Créé',
            'facturee' => 'Facturée',
            'cloturee' => 'Clôturée',
            'annule' => 'Annulé',
        ];

        $counts = collect($statuts)
            ->map(fn ($label, $key) => Commande::query()
                ->where('statut_commande', $key)
                ->count());

        return [
            'datasets' => [
                [
                    'label' => 'Commandes',
                    'data' => $counts->values()->all(),
                    'backgroundColor' => [
                        '#6b7280', // gris - créé
                        '#f59e0b', // warning - facturée
                        '#22c55e', // success - clôturée
                        '#ef4444', // danger - annulé
                    ],
                ],
            ],
            'labels' => array_values($statuts),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
