<?php

namespace App\Filament\Resources\Commandes\Pages;

use App\Filament\Resources\Commandes\CommandeResource;
use App\Models\Magasin;
use Filament\Resources\Pages\CreateRecord;

class CreateCommande extends CreateRecord
{
    protected static string $resource = CommandeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['montant_total'] = $this->calculateMontantTotal($data['items'] ?? []);

        // 'items' isn't a column on commandes — handled in afterCreate() instead
        unset($data['items']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $items = $this->data['items'] ?? [];
        $magasinIds = Magasin::query()->where('active', true)->pluck('id');

        foreach ($items as $item) {
            $puAchatNet = $this->calculatePuAchatNet($item);

            $detail = $this->record->detailCommandes()->create([
                'product_id'   => $item['product_id'],
                'pu_achat_HT'  => $item['pu_achat_HT'],
                'tax'          => $item['tax'],
                'taux_remise'  => $item['taux_remise'],
                'pu_achat_net' => $puAchatNet,
                'quantite'     => $item['quantite'],
            ]);

            foreach ($magasinIds as $magasinId) {
                $qte = (float) ($item["repartition_{$magasinId}"] ?? 0);

                if ($qte > 0) {
                    $detail->repartitions()->create([
                        'magasin_id' => $magasinId,
                        'quantite'   => $qte,
                    ]);
                }
            }
        }
    }

    private function calculatePuAchatNet(array $item): float
    {
        $ht = (float) ($item['pu_achat_HT'] ?? 0);
        $tax = (float) ($item['tax'] ?? 0);
        $remise = (float) ($item['taux_remise'] ?? 0);

        return $ht + ($ht * $tax / 100) - ($ht * $remise / 100);
    }

    private function calculateMontantTotal(array $items): float
    {
        return collect($items)->sum(function (array $item) {
            $puNet = $this->calculatePuAchatNet($item);
            $qte = (float) ($item['quantite'] ?? 0);

            return $puNet * $qte;
        });
    }
}
