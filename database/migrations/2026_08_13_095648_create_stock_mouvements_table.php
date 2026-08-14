<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_mouvements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_lot_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('type', ['entree', 'ajustement'])
                ->default('entree')
                ->comment('entree = reception commande (crée un nouveau lot), ajustement = correction manuelle. "sortie" (vente) sera ajouté avec la table de vente.');
            $table->integer('quantite');
            $table->integer('quantite_avant');
            $table->integer('quantite_apres');
            $table->date('date_mouvement');
            $table->foreignId('reception_commande_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('detail_reception_commande_id')->nullable()->constrained()->nullOnDelete();

            $table->text('commentaire')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'type']);
            $table->index('stock_lot_id');
            $table->index('reception_commande_id');
            $table->index('date_mouvement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_mouvements');
    }
};
