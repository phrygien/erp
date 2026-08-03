<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Ligne;
use App\Models\Marque;
use App\Models\Product;
use App\Models\Type;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Marques
        $marques = Marque::query()->get();
        if ($marques->isEmpty()) {
            $marques = collect(range(1, 5))->map(fn ($i) => Marque::create([
                'code' => 'MRQ' . $i,
                'name' => 'Marque ' . $i,
                'state' => 'active',
            ]));
        }

        // 2. Categories (liées à une marque)
        $categories = Category::query()->get();
        if ($categories->isEmpty()) {
            $categories = collect();
            foreach ($marques as $marque) {
                for ($i = 1; $i <= 2; $i++) {
                    $categories->push(Category::create([
                        'code' => 'CAT-' . $marque->id . '-' . $i,
                        'name' => 'Catégorie ' . Str::random(5),
                        'marque_id' => $marque->id,
                        'state' => 'active',
                    ]));
                }
            }
        }

        // 3. Lignes (liées à une catégorie + sa marque)
        $lignes = Ligne::query()->get();
        if ($lignes->isEmpty()) {
            $lignes = collect();
            foreach ($categories as $category) {
                $lignes->push(Ligne::create([
                    'code' => 'LIG-' . $category->id,
                    'name' => 'Ligne ' . Str::random(5),
                    'category_id' => $category->id,
                    'marque_id' => $category->marque_id,
                    'state' => 'active',
                ]));
            }
        }

        // 4. Types (indépendants)
        $types = Type::query()->get();
        if ($types->isEmpty()) {
            $types = collect(range(1, 5))->map(fn () => Type::create([
                'name' => 'Type ' . Str::random(5),
                'state' => 'active',
            ]));
        }

        $devises = ['EUR', 'USD', 'MUR'];
        $tvaRates = [0, 5, 10, 15, 20];
        $statutsParkod = ['ok', 'valide', 'en_attente', 'erreur'];

        for ($i = 1; $i <= 50; $i++) {
            // On part d'une ligne au hasard pour garantir la cohérence category/marque/ligne
            $ligne = $lignes->random();

            Product::create([
                'product_code' => 'PROD-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'category_id' => $ligne->category_id,
                'marque_id' => $ligne->marque_id,
                'type_id' => $types->random()->id,
                'ligne_id' => $ligne->id,
                'designation' => 'Produit ' . fake()->words(3, true),
                'designation_variant' => fake()->words(2, true),
                'article' => 'ART-' . fake()->unique()->numberBetween(1000, 9999),
                'ref_fabri_n_1' => 'REF-' . strtoupper(Str::random(8)),
                'EAN' => fake()->unique()->ean13(),
                'pght_parkod' => fake()->randomFloat(2, 5, 500),
                'tva' => fake()->randomElement($tvaRates),
                'devise' => fake()->randomElement($devises),
                'hs_code' => fake()->numerify('########'),
                'statut_parkod' => fake()->randomElement($statutsParkod),
                'state' => fake()->randomElement(['active', 'inactive']),
            ]);
        }
    }
}
