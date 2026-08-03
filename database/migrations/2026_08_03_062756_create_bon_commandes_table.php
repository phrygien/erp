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
        Schema::create('bon_commandes', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('commande_id')->constrained();
            $table->string('code_fournisseur');
            $table->string('numero_compte')->nullable();
            $table->date('date_commande')->nullable();
            $table->date('date_livraison')->nullable();

            $table->foreignId('magasin_facturation_id')
                ->constrained('magasins');

            $table->foreignId('magasin_livraison_id')
                ->constrained('magasins');

            $table->decimal('montant_commande', 10, 2);
            $table->enum('statut_commande', ['annule', 'cree', 'facturee', 'cloturee'])->default('cree');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bon_commandes');
    }
};
