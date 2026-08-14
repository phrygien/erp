<?php

namespace App\Filament\Exports;

use App\Models\Stock;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class StockExporter extends Exporter
{
    protected static ?string $model = Stock::class;

    public function getFormats(): array
    {
        return [
            ExportFormat::Xlsx,
        ];
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('product.product_code')
                ->label('Code produit'),

            ExportColumn::make('product.EAN')
                ->label('EAN'),

            ExportColumn::make('product.designation')
                ->label('Désignation'),

            ExportColumn::make('product.category.name')
                ->label('Catégorie'),

            ExportColumn::make('product.marque.name')
                ->label('Marque'),

            ExportColumn::make('product.type.name')
                ->label('Type'),

            ExportColumn::make('quantite')
                ->label('Quantité'),

            ExportColumn::make('gen_code')
                ->label('Code stock'),

            ExportColumn::make('created_at')
                ->label('Créé le'),

            ExportColumn::make('updated_at')
                ->label('Dernier mouvement'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $failedRowsCount = $export->getFailedRowsCount();

        $body = 'Votre export du stock est terminé et ' . number_format($export->successful_rows) . ' ' . str('ligne')->plural($export->successful_rows) . ' ont été exportées.';

        if ($failedRowsCount) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('ligne')->plural($failedRowsCount) . ' n\'ont pas pu être exportées.';
        }

        return $body;
    }
}
