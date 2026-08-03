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
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->decimal('montant_minimum', 10, 2)->nullable();
            $table->decimal('montant_total', 10, 2);
            $table->decimal('remise_facture', 10, 2);
            $table->foreignId('fournisseur_id')->constrained();
            $table->foreignId('magasin_id')->constrained();
            $table->integer('nombre_jours')->nullable();
            $table->enum('etat_commande', ['pre_commande', 'commande'])->default('pre_commande');
            $table->enum('statut_commande', ['annule', 'cree', 'facturee', 'cloturee'])->default('cree');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::create('commande_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained()->cascadeOnDelete();
            $table->string('champ'); // 'etat_commande' ou 'statut_commande'
            $table->string('ancienne_valeur')->nullable();
            $table->string('nouvelle_valeur');
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->index(['commande_id', 'champ']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commande_status_histories');
        Schema::dropIfExists('commandes');
    }
};
