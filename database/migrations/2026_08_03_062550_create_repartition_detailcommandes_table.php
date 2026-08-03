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
        Schema::create('repartition_detailcommandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('detail_commande_id')->constrained('detail_commandes')->cascadeOnDelete();
            $table->foreignId('magasin_id')->constrained();
            $table->integer('quantite');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['detail_commande_id', 'magasin_id'], 'detail_magasin_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repartition_detailcommandes');
    }
};
