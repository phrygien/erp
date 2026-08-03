<?php

namespace App\Filament\Resources\Commandes\Widgets;

use App\Models\Commande;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CommandeStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Nombre de commandes par jour sur les 7 derniers jours (pour le sparkline)
        $trend = collect(range(6, 0))
            ->map(fn ($daysAgo) => Commande::query()
                ->whereDate('created_at', now()->subDays($daysAgo))
                ->count())
            ->all();

        return [
            Stat::make('Orders', Commande::query()->count())
                ->chart($trend)
                ->color('gray'),

            Stat::make('Open orders', Commande::query()
                ->whereNotIn('statut_commande', ['cloturee', 'annule'])
                ->count())
                ->color('gray'),

            Stat::make('Average price', number_format(
                Commande::query()->avg('montant_total') ?? 0,
                2
            ))
                ->color('gray'),
        ];
    }
}
