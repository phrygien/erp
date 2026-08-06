<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_reception_commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_commande_id')->constrained()->cascadeOnDelete();
            $table->foreignId('detail_commande_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('qte_recue')->default(0);
            $table->integer('qte_invendable')->default(0);
            $table->string('motif_invendable')->nullable();
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->index(['reception_commande_id', 'detail_commande_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_reception_commandes');
    }
};
