<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_factures', function (Blueprint $table) {
            $table->id();

            $table->foreignId('facture_id')->constrained('factures')->cascadeOnDelete();
            $table->foreignId('detail_commande_id')->constrained('detail_commandes')->restrictOnDelete();

            // Quantités : commandée, réellement livrée/reçue, et facturée
            // (souvent différentes en réalité — écarts, ruptures, retours partiels)
            $table->integer('quantite_commande')->default(0);
            $table->integer('quantite_facturee')->default(0);

            // Prix unitaire, indispensable pour recalculer/auditer une ligne
            // sans dépendre uniquement des montants agrégés
            $table->decimal('prix_unitaire_ht', 10, 2)->default(0);

            $table->decimal('montant_ht', 10, 2)->default(0);
            $table->decimal('montant_remise', 10, 2)->default(0);
            $table->decimal('montant_final_ht', 10, 2)->default(0);
            $table->decimal('montant_final_net', 10, 2)->default(0);

            $table->timestamps();

            // Une ligne de commande ne doit apparaître qu'une seule fois
            // par facture (sinon double-facturation possible)
            $table->unique(['facture_id', 'detail_commande_id']);

            $table->index('facture_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_factures');
    }
};
