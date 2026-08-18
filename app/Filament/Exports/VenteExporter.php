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

            ExportColumn::make('product.designation')
                ->label('Produit'),

            ExportColumn::make('product.EAN')
                ->label('EAN'),

            ExportColumn::make('magasin.name')
                ->label('Magasin'),

            ExportColumn::make('canal_vente')
                ->label('Canal'),

            ExportColumn::make('caisseSession.caisse.name')
                ->label('Caisse'),

            ExportColumn::make('caisseSession.responsable.name')
                ->label('Responsable caisse'),

            ExportColumn::make('quantite')
                ->label('Quantité'),

            ExportColumn::make('montant_total_ht_vente')
                ->label('Total HT (EUR)'),

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
