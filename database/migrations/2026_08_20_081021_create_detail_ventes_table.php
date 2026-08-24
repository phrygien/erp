<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('details_ventes', function (Blueprint $table) {
            $table->id();

            // Vente "en-tête" à laquelle appartient cette ligne. Si la
            // vente est supprimée, ses lignes le sont aussi (elles n'ont
            // pas de sens seules).
            $table->foreignId('vente_id')->constrained('ventes')->cascadeOnDelete();

            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('stock_lot_id')->constrained('stock_lots')->restrictOnDelete();

            $table->integer('quantite')->default(0);
            $table->decimal('prix_unitaire', 15, 2)->default(0);
            $table->decimal('montant_total_ligne', 15, 2)->default(0);

            $table->timestamps();

            $table->index(['vente_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('details_ventes');
    }
};
