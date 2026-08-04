<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repartition_detailcommandes', function (Blueprint $table) {
            $table->foreignId('commande_id')
                ->nullable()
                ->after('detail_commande_id')
                ->constrained()
                ->cascadeOnDelete();
        });

        // Backfill : déduit commande_id depuis detail_commandes pour les lignes
        // existantes. Sous-requête (et non UPDATE...JOIN) pour rester compatible
        // SQLite et MySQL.
        DB::statement('
            UPDATE repartition_detailcommandes
            SET commande_id = (
                SELECT commande_id
                FROM detail_commandes
                WHERE detail_commandes.id = repartition_detailcommandes.detail_commande_id
            )
        ');
    }

    public function down(): void
    {
        Schema::table('repartition_detailcommandes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('commande_id');
        });
    }
};
