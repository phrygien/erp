<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Ligne;
use App\Models\Marque;
use App\Models\Product;
use App\Models\Type;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importParkod')
                ->label('Importer PARKOD')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->modalHeading('Importer un fichier PARKOD')
                ->modalSubmitActionLabel('Importer')
                ->modalWidth('lg')
                ->schema([
                    FileUpload::make('attachments')
                        ->label('Fichiers PARKOD (.txt)')
                        ->multiple()
                        ->acceptedFileTypes(['text/plain', '.txt'])
                        ->maxSize(51200)
                        ->required()
                        ->disk('local')
                        ->directory('parkod-imports')
                        ->preserveFilenames()
                        ->helperText('Formats acceptés : .txt (max 50 Mo par fichier)'),
                ])
                ->action(function (array $data): void {
                    $new = 0;
                    $update = 0;
                    $errors = 0;

                    foreach ($data['attachments'] as $path) {
                        $content = Storage::disk('local')->get($path);
                        $lines = explode("\n", $content);

                        foreach ($lines as $line) {
                            if (trim($line) === '') {
                                continue;
                            }

                            $row = explode(';', $line);

                            if (! static::isValidRow($row)) {
                                $errors++;
                                continue;
                            }

                            $ean = trim($row[1] ?? '');
                            $code_marque = trim($row[2] ?? '');
                            $code_categorie = trim($row[3] ?? '');
                            $code_produit = trim($row[4] ?? '');
                            $code_ligne = trim($row[5] ?? '');
                            $designation_1 = trim($row[6] ?? '');
                            $designation_2 = trim($row[7] ?? '');
                            $marque_name = trim($row[9] ?? '');
                            $libelle_ligne = trim($row[10] ?? '');
                            $type_produit = trim($row[12] ?? '');
                            $ref_fabr_n_1 = trim($row[14] ?? '');
                            $tva = trim($row[16] ?? '');
                            $pght_parkod = trim($row[17] ?? '');
                            $code_winparf = trim($row[23] ?? '');
                            $devise = trim($row[24] ?? '');
                            $libelle_court = trim($row[25] ?? '');
                            $HS_code = trim($row[26] ?? '');

                            // Marque
                            $marque = Marque::firstOrCreate(
                                ['code' => $code_marque],
                                ['name' => $marque_name, 'state' => 'active']
                            );

                            // Categorie
                            $category = Category::firstOrCreate(
                                ['code' => $code_categorie, 'marque_id' => $marque->id],
                                ['name' => $marque_name.' '.$code_categorie, 'state' => 'active']
                            );

                            // Ligne
                            $ligne = Ligne::firstOrCreate(
                                ['code' => $code_ligne, 'category_id' => $category->id, 'marque_id' => $marque->id],
                                ['name' => $libelle_ligne, 'state' => 'active']
                            );

                            // Type (pas de code/FK, on matche par nom)
                            $type = Type::firstOrCreate(
                                ['name' => $type_produit],
                                ['state' => 'active']
                            );

                            // Produit
                            $product = Product::where('EAN', $ean)->first();

                            $payload = [
                                'product_code' => $code_produit,
                                'marque_id' => $marque->id,
                                'category_id' => $category->id,
                                'ligne_id' => $ligne->id,
                                'type_id' => $type->id,
                                'designation' => $designation_1,
                                'designation_variant' => $designation_2,
                                'article' => $libelle_court,
                                'ref_fabri_n_1' => $ref_fabr_n_1,
                                'EAN' => $ean,
                                'pght_parkod' => $pght_parkod,
                                'tva' => $tva,
                                'devise' => $devise,
                                'statut_parkod' => $code_winparf,
                                'hs_code' => $HS_code,
                            ];

                            if ($product) {
                                $product->update($payload);
                                $update++;
                            } else {
                                Product::create($payload + ['state' => 'active']);
                                $new++;
                            }
                        }

                        Storage::disk('local')->delete($path);
                    }

                    Notification::make()
                        ->title('Importation PARKOD terminée')
                        ->body("{$new} produit(s) créé(s), {$update} mis à jour, {$errors} ligne(s) ignorée(s)")
                        ->success()
                        ->send();
                }),
        ];
    }

    private static function isValidRow(array $row): bool
    {
        if (count($row) < 27) {
            return false;
        }

        if (trim($row[0]) !== '01') {
            return false;
        }

        if (trim($row[1]) === '') {
            return false;
        }

        $validStatuts = ['S', 'C', 'P', 'M', 'N', 'E', 'V', 'F'];
        if (! in_array(strtoupper(trim($row[23])), $validStatuts, true)) {
            return false;
        }

        return true;
    }
}
