<?php

namespace App\Filament\Widgets;

use App\Models\DetailVente;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TopProduitsVendusWidget extends BaseWidget
{
    protected static ?string $heading = 'Top 100 des produits les plus vendus';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('rang')
                    ->label('#')
                    ->rowIndex(),

                Tables\Columns\TextColumn::make('product_code')
                    ->label('Code')
                    ->searchable(),

                Tables\Columns\TextColumn::make('designation')
                    ->label('Désignation')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('marque_nom')
                    ->label('Marque')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category_nom')
                    ->label('Catégorie')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('quantite_vendue')
                    ->label('Qté vendue')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('montant_vendu')
                    ->label('Montant total')
                    ->money('EUR') // adapte la devise si besoin
                    ->sortable()
                    ->alignEnd(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('periode')
                    ->label('Période')
                    ->options([
                        'jour' => "Aujourd'hui",
                        'semaine' => 'Cette semaine',
                        'mois' => 'Ce mois-ci',
                        'annee' => 'Cette année',
                        'tout' => 'Toute la période',
                    ])
                    ->default('mois')
                    ->query(fn (Builder $query, array $data) => $this->appliquerFiltrePeriode(
                        $query,
                        $data['value'] ?? 'mois',
                    )),
            ])
            ->defaultSort('quantite_vendue', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(100);
    }

    /**
     * Agrège les lignes de détail par produit. On joint `ventes` pour
     * pouvoir filtrer par date réelle de la transaction (et non de la
     * ligne de détail), et `products`/`marques`/`categories` pour
     * l'affichage.
     *
     * IMPORTANT : `product_id` est aliasé en `id` dans le SELECT. Filament
     * a besoin d'un champ `id` pour identifier chaque ligne du tableau
     * (getTableRecordKey). Comme chaque ligne de ce résultat groupé
     * correspond à un produit unique, product_id fait un identifiant de
     * ligne valide.
     */
    protected function getTableQuery(): Builder
    {
        return DetailVente::query()
            ->join('ventes', 'ventes.id', '=', 'details_ventes.vente_id')
            ->join('products', 'products.id', '=', 'details_ventes.product_id')
            ->leftJoin('marques', 'marques.id', '=', 'products.marque_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('
                details_ventes.product_id as id,
                products.product_code,
                products.designation,
                marques.name as marque_nom,
                categories.name as category_nom,
                SUM(details_ventes.quantite) as quantite_vendue,
                SUM(details_ventes.montant_total_ligne) as montant_vendu
            ')
            ->groupBy(
                'details_ventes.product_id',
                'products.product_code',
                'products.designation',
                'marques.name',
                'categories.name',
            )
            ->limit(100);
    }

    protected function appliquerFiltrePeriode(Builder $query, string $periode): Builder
    {
        return match ($periode) {
            'jour' => $query->whereDate('ventes.created_at', Carbon::today()),
            'semaine' => $query->whereBetween('ventes.created_at', [
                Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek(),
            ]),
            'annee' => $query->whereBetween('ventes.created_at', [
                Carbon::now()->startOfYear(), Carbon::now()->endOfYear(),
            ]),
            'tout' => $query,
            default => $query->whereBetween('ventes.created_at', [
                Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth(),
            ]),
        };
    }
}
