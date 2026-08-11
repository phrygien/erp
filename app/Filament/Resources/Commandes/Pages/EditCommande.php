<?php

namespace App\Filament\Resources\Commandes\Pages;

use App\Filament\Resources\Commandes\CommandeResource;
use App\Models\Magasin;
use App\Models\RepartitionDetailcommandeHistorique;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

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

        DB::transaction(function () use ($items, $magasinIds) {
            // Simplest correct approach: wipe and recreate details + repartitions.
            //
            // IMPORTANT : on matérialise la collection avec get() AVANT de
            // supprimer quoi que ce soit. Utiliser ->each() ici est dangereux
            // car il pagine en interne (chunk) : supprimer des lignes pendant
            // l'itération décale les offsets et fait sauter des enregistrements,
            // ce qui laissait certains detail_commandes avec des repartitions
            // encore présentes en base au moment du delete() -> violation FK
            // (ex: "delete from detail_commandes where id = 28").
            $details = $this->record->detailCommandes()->get();

            foreach ($details as $detail) {
                $repartitionIds = $detail->repartitions()->pluck('id');

                // 1. Supprimer d'abord les historiques (petits-enfants).
                //    repartitions()->delete() est un bulk delete qui NE
                //    déclenche PAS l'event Eloquent "deleted", donc les
                //    historiques ne seraient jamais nettoyés autrement et
                //    pouvaient laisser des lignes orphelines bloquant la
                //    suppression en cascade.
                if ($repartitionIds->isNotEmpty()) {
                    RepartitionDetailcommandeHistorique::query()
                        ->whereIn('repartition_detailcommande_id', $repartitionIds)
                        ->delete();
                }

                // 2. Puis les répartitions
                $detail->repartitions()->delete();

                // 3. detail_factures et detail_reception_commandes ont aussi
                //    une FK vers detail_commandes (detail_commande_id), sans
                //    modèle Eloquent connu ici : on passe par le Query
                //    Builder. C'était la cause réelle du blocage FK sur
                //    "delete from detail_commandes where id = 28", que le
                //    nettoyage des repartitions/historiques seul ne couvrait
                //    pas.
                DB::table('detail_factures')
                    ->where('detail_commande_id', $detail->id)
                    ->delete();

                DB::table('detail_reception_commandes')
                    ->where('detail_commande_id', $detail->id)
                    ->delete();

                // 4. Puis le détail lui-même
                $detail->delete();
            }

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
        });

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
