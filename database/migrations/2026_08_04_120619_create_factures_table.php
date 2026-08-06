<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->string('numero_facture')->unique();
            $table->string('libelle_facture');

            // bon_commande_id est la source de vérité : le fournisseur et le
            // magasin s'en déduisent (bon_commande -> commande -> fournisseur)
            $table->foreignId('bon_commande_id')->constrained()->restrictOnDelete();
            $table->foreignId('fournisseur_id')->constrained()->restrictOnDelete();

            $table->date('date_facture');
            $table->date('date_echeance')->nullable();

            // Mêmes précisions (10,2) que commandes.montant_total et
            // bon_commandes.montant_commande, pour rester cohérent
            $table->decimal('montant_ht', 10, 2)->default(0);
            $table->decimal('taux_tva', 5, 2)->default(0);
            $table->decimal('montant_tva', 10, 2)->default(0);
            $table->decimal('remise', 10, 2)->default(0);
            $table->decimal('montant_ttc', 10, 2)->default(0);

            $table->enum('type', ['commande', 'retour_commande'])->default('commande');
            $table->enum('statut', ['encours', 'paye', 'rejete'])->default('encours');
            $table->boolean('archivage')->default(false);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Un bon de commande peut avoir une facture normale ET un avoir
            // (retour_commande), mais pas deux fois le même type
            $table->unique(['bon_commande_id', 'type']);

            $table->index(['statut', 'archivage']);
            $table->index('date_facture');
        });

        // Même logique de traçabilité que commande_status_histories,
        // pour garder un historique des changements de statut de facture
        Schema::create('facture_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facture_id')->constrained()->cascadeOnDelete();
            $table->string('ancienne_valeur')->nullable();
            $table->string('nouvelle_valeur');
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->index('facture_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facture_status_histories');
        Schema::dropIfExists('factures');
    }
};
