<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('magasins', function (Blueprint $table) {
            // Identifie le magasin qui sert de dépôt central : c'est le seul
            // pour lequel le stock est suivi (stocks, stock_lots,
            // stock_mouvements). Les autres magasins (points de vente, sites
            // "en ligne") ne portent pas de stock propre.
            $table->boolean('depot_central')->default(false)->after('type');
        });

        // SQLite ne supporte pas les contraintes CHECK "un seul true" au
        // niveau colonne : on utilise un index unique partiel, qui
        // n'indexe (et donc ne contraint) que les lignes où
        // depot_central = 1. Ça empêche d'avoir deux dépôts centraux, sans
        // limiter le nombre de magasins avec depot_central = 0.
        DB::statement(
            'CREATE UNIQUE INDEX magasins_depot_central_unique ON magasins (depot_central) WHERE depot_central = 1'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS magasins_depot_central_unique');

        Schema::table('magasins', function (Blueprint $table) {
            $table->dropColumn('depot_central');
        });
    }
};
