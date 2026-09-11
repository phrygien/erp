<?php

namespace App\Filament\Widgets;

use App\Models\Magasin;
use App\Models\Vente;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CaParMagasinWidget extends ChartWidget
{
    protected ?string $heading = 'Chiffre d\'affaires par magasin';

    // Demi-largeur pour un affichage côte à côte avec CaEvolutionWidget
    // sur une grille dashboard à 2 colonnes.
    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    public ?string $filter = 'mois';

    protected function getFilters(): ?array
    {
        return [
            'jour' => "Aujourd'hui",
            'semaine' => 'Cette semaine',
            'mois' => 'Ce mois-ci',
            'annee' => 'Cette année',
        ];
    }

    protected function getData(): array
    {
        [$debut, $fin] = $this->intervallePourFiltre($this->filter ?? 'mois');

        // On part des magasins actifs pour garantir que chaque magasin
        // apparaît dans le graphique, même sans vente sur la période
        // (barre à zéro plutôt qu'un magasin manquant).
        $magasins = Magasin::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $ventesParMagasin = Vente::query()
            ->whereBetween('created_at', [$debut, $fin])
            ->selectRaw('magasin_id, SUM(montant_total) as total')
            ->groupBy('magasin_id')
            ->pluck('total', 'magasin_id');

        $labels = [];
        $valeurs = [];

        foreach ($magasins as $magasin) {
            $labels[] = $magasin->name;
            $valeurs[] = (float) ($ventesParMagasin[$magasin->id] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => "Chiffre d'affaires (MUR)",
                    'data' => $valeurs,
                    'backgroundColor' => [
                        '#22c55e', '#3b82f6', '#f97316', '#a855f7',
                        '#ef4444', '#14b8a6', '#eab308', '#ec4899',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function intervallePourFiltre(string $periode): array
    {
        return match ($periode) {
            'jour' => [Carbon::today(), Carbon::now()->endOfDay()],
            'semaine' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'annee' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            default => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };
    }
}
