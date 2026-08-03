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
        Schema::create('detail_commandes', function (Blueprint $table) {
            $table->id();
            $table->decimal('pu_achat_HT', 10, 2);
            $table->decimal('tax', 10, 2);
            $table->decimal('taux_remise', 10, 2);
            $table->decimal('pu_achat_net', 10, 2);
            $table->foreignId('commande_id')->constrained();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('quantite');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_commandes');
    }
};
