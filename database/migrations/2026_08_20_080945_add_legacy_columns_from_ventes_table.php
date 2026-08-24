<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            // Les clés étrangères doivent être supprimées avant les
            // colonnes elles-mêmes.
            $table->dropForeign(['product_id']);
            $table->dropForeign(['stock_lot_id']);
        });

        Schema::table('ventes', function (Blueprint $table) {
            $table->dropColumn([
                'product_id',
                'stock_lot_id',
                'quantite',
                'montant_total_ht_vente',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('numero_vente')->constrained('products')->restrictOnDelete();
            $table->foreignId('stock_lot_id')->nullable()->after('product_id')->constrained('stock_lots')->restrictOnDelete();
            $table->integer('quantite')->nullable()->after('stock_lot_id');
            $table->decimal('montant_total_ht_vente', 15, 2)->nullable()->after('quantite');
        });
    }
};
