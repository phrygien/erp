<?php

namespace App\Filament\Resources\Commandes\Pages;

use App\Filament\Resources\Commandes\CommandeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCommandes extends ListRecords
{
    protected static string $resource = CommandeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'tous' => Tab::make('Tous'),

            'cree' => Tab::make('Créé')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut_commande', 'cree')),

            'facturee' => Tab::make('Facturée')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut_commande', 'facturee')),

            'cloturee' => Tab::make('Clôturée')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut_commande', 'cloturee')),

            'annule' => Tab::make('Annulé')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('statut_commande', 'annule')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\Commandes\Widgets\CommandeStatsOverview::class,
        ];
    }
}
