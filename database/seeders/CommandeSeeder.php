<?php

namespace Database\Seeders;

use App\Models\Commande;
use App\Models\DetailCommande;
use App\Models\Fournisseur;
use App\Models\Magasin;
use App\Models\Product;
use App\Models\RepartitionDetailcommande;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommandeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fournisseurs = Fournisseur::query()->pluck('id');
        $magasins = Magasin::query()->pluck('id');
        $products = Product::query()->pluck('id');
        $userIds = User::query()->pluck('id');

        if ($fournisseurs->isEmpty() || $magasins->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Fournisseurs, magasins ou products manquants. Lance leurs seeders avant CommandeSeeder.');
            return;
        }

        $etats = ['pre_commande', 'commande'];
        $statuts = ['annule', 'cree', 'facturee', 'cloturee'];

        for ($i = 1; $i <= 10; $i++) {
            $commande = Commande::create([
                'libelle' => 'Commande ' . fake()->words(3, true),
                'montant_minimum' => fake()->randomFloat(2, 100, 500),
                'montant_total' => 0, // recalculé après création des détails
                'remise_facture' => fake()->randomFloat(2, 0, 50),
                'fournisseur_id' => $fournisseurs->random(),
                'magasin_id' => $magasins->random(),
                'nombre_jours' => fake()->numberBetween(1, 30),
                'etat_commande' => fake()->randomElement($etats),
                'statut_commande' => fake()->randomElement($statuts),
                'created_by' => $userIds->isNotEmpty() ? $userIds->random() : null,
            ]);

            $montantTotal = 0;

            // 2 à 5 lignes de détail par commande
            $nombreDetails = fake()->numberBetween(2, 5);

            for ($d = 1; $d <= $nombreDetails; $d++) {
                $puAchatHT = fake()->randomFloat(2, 10, 300);
                $tax = fake()->randomFloat(2, 0, 20);
                $tauxRemise = fake()->randomFloat(2, 0, 15);
                $puAchatNet = round($puAchatHT + ($puAchatHT * $tax / 100) - ($puAchatHT * $tauxRemise / 100), 2);
                $quantite = fake()->numberBetween(10, 200);

                $detail = DetailCommande::create([
                    'pu_achat_HT' => $puAchatHT,
                    'tax' => $tax,
                    'taux_remise' => $tauxRemise,
                    'pu_achat_net' => $puAchatNet,
                    'commande_id' => $commande->id,
                    'product_id' => $products->random(),
                    'quantite' => $quantite,
                ]);

                $montantTotal += $puAchatNet * $quantite;

                // Répartition de la quantité entre 1 à 3 magasins distincts
                $magasinsChoisis = $magasins->random(min(fake()->numberBetween(1, 3), $magasins->count()));
                $magasinsChoisis = $magasinsChoisis instanceof \Illuminate\Support\Collection
                    ? $magasinsChoisis
                    : collect([$magasinsChoisis]);

                $quantiteRestante = $quantite;
                $nombreMagasins = $magasinsChoisis->count();

                $magasinsChoisis->values()->each(function ($magasinId, $index) use (&$quantiteRestante, $nombreMagasins, $detail, $userIds) {
                    $estDernier = $index === $nombreMagasins - 1;

                    $quantitePart = $estDernier
                        ? $quantiteRestante
                        : (int) round($quantiteRestante / ($nombreMagasins - $index) * fake()->randomFloat(2, 0.5, 1));

                    $quantitePart = max(1, min($quantitePart, $quantiteRestante));

                    RepartitionDetailcommande::create([
                        'detail_commande_id' => $detail->id,
                        'magasin_id' => $magasinId,
                        'quantite' => $quantitePart,
                        'created_by' => $userIds->isNotEmpty() ? $userIds->random() : null,
                    ]);

                    $quantiteRestante -= $quantitePart;
                });
            }

            $commande->update(['montant_total' => round($montantTotal, 2)]);
        }
    }
}
