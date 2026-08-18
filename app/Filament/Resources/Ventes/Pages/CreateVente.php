<?php

namespace App\Filament\Resources\Ventes\Pages;

use App\Filament\Resources\Ventes\VenteResource;
use App\Models\StockLot;
use App\Models\Vente;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateVente extends CreateRecord
{
    protected static string $resource = VenteResource::class;

    /**
     * Le formulaire soumet un Repeater "items" (plusieurs produits) alors
     * que le modèle Vente = une ligne par produit. On crée donc une Vente
     * par item, dans une transaction, et on retourne la première comme
     * "record" de la page (comportement standard Filament après création).
     */
    protected function handleRecordCreation(array $data): Model
    {
        $items = $data['items'] ?? [];

        if (empty($items)) {
            Notification::make()
                ->title('Ajoutez au moins un produit.')
                ->danger()
                ->send();

            $this->halt();
        }

        $ventes = DB::transaction(function () use ($data, $items) {
            $created = [];

            foreach ($items as $item) {
                $lot = StockLot::lockForUpdate()->find($item['stock_lot_id'] ?? null);

                if (! $lot || $lot->quantite_restante < (int) ($item['quantite'] ?? 0)) {
                    throw new \RuntimeException('Stock insuffisant pour un des produits sélectionnés.');
                }

                $created[] = Vente::create([
                    'product_id' => $item['product_id'],
                    'magasin_id' => $data['magasin_id'],
                    'stock_lot_id' => $lot->id,
                    'canal_vente' => $data['canal_vente'],
                    'caisse_session_id' => $data['caisse_session_id'] ?? null,
                    'quantite' => $item['quantite'],
                    'montant_total_ht_vente' => round(
                        (float) $item['quantite'] * (float) ($item['prix_unitaire'] ?? 0),
                        2
                    ),
                ]);

                // NB : contrairement au flux POS (PosCaisse::valider()), la
                // décrémentation du stock (StockMouvement::enregistrerSortie)
                // n'est pas faite ici. Ajoute-la si ce formulaire doit aussi
                // affecter le stock physique.
            }

            return $created;
        });

        return $ventes[0];
    }
}
