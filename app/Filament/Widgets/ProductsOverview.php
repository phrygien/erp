<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Products', Product::count())
                ->color('primary'),

            Stat::make('Produits actifs', Product::where('state', 'active')->count())
                ->color('success'),

            Stat::make('Produits inactifs', Product::where('state', 'inactive')->count())
                ->color('danger'),
        ];
    }
}
