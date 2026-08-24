<?php

namespace App\Filament\Exports;

use App\Models\Vente;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class VenteExporter extends Exporter
{
    protected static ?string $model = Vente::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),

            ExportColumn::make('numero_vente')
                ->label('N° de vente'),

            ExportColumn::make('magasin.name')
                ->label('Magasin'),

            ExportColumn::make('canal_vente')
                ->label('Canal'),

            ExportColumn::make('caisseSession.caisse.name')
                ->label('Caisse'),

            ExportColumn::make('caisseSession.responsable.name')
                ->label('Responsable caisse'),

            // Une Vente porte désormais plusieurs produits (une ligne par
            // article dans details_ventes) : product/EAN/quantite ne sont
            // plus des colonnes de Vente. On exporte à la place un résumé
            // (nombre d'articles distincts, quantité totale) au niveau de
            // la transaction ; pour le détail ligne par ligne, voir
            // DetailVenteExporter.
            ExportColumn::make('nombre_articles')
                ->label('Nombre d\'articles')
                ->state(fn (Vente $record) => $record->details()->count()),

            ExportColumn::make('quantite_totale')
                ->label('Quantité totale')
                ->state(fn (Vente $record) => $record->details()->sum('quantite')),

            ExportColumn::make('montant_total')
                ->label('Total (EUR)'),

            ExportColumn::make('created_at')
                ->label('Date de vente'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Votre export de ventes est terminé et contient ' . number_format($export->successful_rows) . ' ' . str('ligne')->plural($export->successful_rows) . '.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('ligne')->plural($failedRowsCount) . ' n\'ont pas pu être exportées.';
        }

        return $body;
    }
}
