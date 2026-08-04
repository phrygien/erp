<?php

namespace App\Filament\Resources\Commandes\Pages;

use App\Filament\Resources\Commandes\CommandeResource;
use App\Models\Magasin;
use Filament\Resources\Pages\EditRecord;

class EditCommande extends EditRecord
{
    protected static string $resource = CommandeResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Rehydrate the repeater from detailCommandes + repartitions when the form loads
        $data['items'] = $this->record->detailCommandes->map(function ($detail) {
            $item = [
                'product_id'   => $detail->product_id,
                'pu_achat_HT'  => $detail->pu_achat_HT,
                'tax'          => $detail->tax,
                'taux_remise'  => $detail->taux_remise,
                'quantite'     => $detail->quantite,
            ];

            foreach ($detail->repartitions as $repartition) {
                $item["repartition_{$repartition->magasin_id}"] = $repartition->quantite;
            }

            return $item;
        })->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['montant_total'] = $this->calculateMontantTotal($data['items'] ?? []);
        unset($data['items']);

        return $data;
    }

    protected function afterSave(): void
    {
        $items = $this->data['items'] ?? [];
        $magasinIds = Magasin::query()->where('active', true)->pluck('id');

        // Simplest correct approach: wipe and recreate details + repartitions
        $this->record->detailCommandes()->each(function ($detail) {
            $detail->repartitions()->delete();
            $detail->delete();
        });

        foreach ($items as $item) {
            $detail = $this->record->detailCommandes()->create([
                'product_id'   => $item['product_id'],
                'pu_achat_HT'  => $item['pu_achat_HT'],
                'tax'          => $item['tax'],
                'taux_remise'  => $item['taux_remise'],
                'quantite'     => $item['quantite'],
                // pu_achat_net n'est pas passé ici : le hook saving() de
                // DetailCommande le recalcule systématiquement à partir de
                // pu_achat_HT / tax / taux_remise. Une seule source de vérité.
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

        // Pas d'appel manuel a une synchro BonCommande ici : chaque creation /
        // suppression de DetailCommande ci-dessus declenche deja
        // Commande::updateMontantTotal(), qui declenche a son tour
        // Commande::syncBonCommande() automatiquement (voir boot() du modele).
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
