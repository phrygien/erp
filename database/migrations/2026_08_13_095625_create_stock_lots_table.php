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
        Schema::create('stock_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            // Origine du lot : une réception de commande. Nullable pour
            // permettre plus tard des lots créés hors réception (inventaire
            // initial, ajustement manuel positif, etc.).
            $table->foreignId('reception_commande_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('detail_reception_commande_id')->nullable()->constrained()->nullOnDelete();

            // Quantité reçue à l'origine (ne change jamais après création)
            // et quantité encore disponible dans ce lot (décrémentée à
            // chaque vente qui y puise). C'est quantite_restante qui pilote
            // le FIFO : on consomme les lots par date_entree croissante
            // tant qu'ils ont quantite_restante > 0.
            $table->integer('quantite_initiale');
            $table->integer('quantite_restante');

            // Coût unitaire au moment de la réception, figé sur le lot pour
            // permettre un calcul de coût des ventes (COGS) fidèle au FIFO
            // réel, indépendamment du prix d'achat courant du produit.
            $table->decimal('pu_achat_net', 10, 2);

            // Date d'entrée en stock : clé de tri pour le FIFO. À renseigner
            // depuis reception_commandes.date_reception (date réelle
            // d'arrivée de la marchandise), pas depuis created_at qui n'est
            // que la date de saisie en base — une réception peut être
            // encodée plusieurs jours après coup.
            $table->date('date_entree');

            $table->enum('statut', ['actif', 'epuise'])->default('actif');

            $table->timestamps();

            // Index composite : c'est la requête FIFO de base
            // ("prochains lots disponibles pour ce produit, du plus ancien
            // au plus récent").
            $table->index(['product_id', 'statut', 'date_entree'], 'stock_lots_fifo_index');

            // reception_commandes a un statut qui évolue (en_cours ->
            // partielle -> complete) : une même ligne de réception peut donc
            // être ré-enregistrée plusieurs fois avant d'être finalisée.
            // Cette contrainte garantit qu'on ne crée jamais deux lots pour
            // la même ligne de réception (idempotence) : au lieu de créer
            // un nouveau lot à chaque sauvegarde, la logique applicative
            // devra faire un upsert sur detail_reception_commande_id.
            $table->unique('detail_reception_commande_id', 'stock_lots_detail_reception_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_lots');
    }
};
