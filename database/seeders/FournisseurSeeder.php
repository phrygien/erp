<?php

namespace Database\Seeders;

use App\Models\Fournisseur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FournisseurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prefixes = [
            'Parfumerie', 'Maison', 'Laboratoire', 'Comptoir', 'Atelier',
            'Groupe', 'Distribution', 'Beauté', 'Essence', 'Cosmétique',
        ];

        $suffixes = [
            'Élégance', 'Prestige', 'Nature', 'Éclat', 'Luxe', 'Aroma',
            'Beauté', 'Fragrance', 'Volupté', 'Divine', 'Rose', 'Orient',
            'Paris', 'Riviera', 'Lumière', 'Sublime', 'Harmonie', 'Cristal',
        ];

        $villes = [
            ['ville' => 'Paris', 'code_postal' => '75008'],
            ['ville' => 'Grasse', 'code_postal' => '06130'],
            ['ville' => 'Lyon', 'code_postal' => '69002'],
            ['ville' => 'Marseille', 'code_postal' => '13001'],
            ['ville' => 'Nice', 'code_postal' => '06000'],
            ['ville' => 'Cannes', 'code_postal' => '06400'],
            ['ville' => 'Bordeaux', 'code_postal' => '33000'],
            ['ville' => 'Lille', 'code_postal' => '59000'],
            ['ville' => 'Strasbourg', 'code_postal' => '67000'],
            ['ville' => 'Toulouse', 'code_postal' => '31000'],
        ];

        for ($i = 1; $i <= 100; $i++) {
            $name = $prefixes[array_rand($prefixes)] . ' ' . $suffixes[array_rand($suffixes)];
            $localisation = $villes[array_rand($villes)];
            $hasRetour = fake()->boolean(60);

            Fournisseur::create([
                'name' => $name,
                'code' => 'FRN-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'raison_social' => $name . ' ' . fake()->randomElement(['SARL', 'SAS', 'SA', 'EURL']),
                'adresse_siege' => fake()->streetAddress(),
                'code_postal' => $localisation['code_postal'],
                'ville' => $localisation['ville'],
                'telephone' => fake()->unique()->numerify('+33 # ## ## ## ##'),
                'fax' => fake()->boolean(40) ? fake()->numerify('+33 # ## ## ## ##') : null,
                'email' => 'contact' . $i . '@' . Str::slug($name) . '.com',
                'adresse_retour' => $hasRetour ? fake()->streetAddress() : null,
                'code_postal_retour' => $hasRetour ? $localisation['code_postal'] : null,
                'ville_retour' => $hasRetour ? $localisation['ville'] : null,
                'state' => fake()->randomElement(['active', 'active', 'active', 'inactive']),
            ]);
        }
    }
}
