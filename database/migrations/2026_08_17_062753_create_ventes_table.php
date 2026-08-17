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
        Schema::create('ventes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            $table->foreignId('stock_lot_id')->constrained('stock_lots')->restrictOnDelete();

            // Canal par lequel la vente a été effectuée. Distinct de
            // magasins.type : un magasin physique peut recevoir une
            // commande en ligne (retrait en magasin), donc le type du
            // magasin seul ne suffit pas à savoir par quel canal la vente
            // est passée.
            $table->enum('canal_vente', ['en_ligne', 'caisse'])->default('caisse');

            // Renseigné uniquement quand canal_vente = 'caisse' : permet de
            // savoir précisément quelle session (et donc quel responsable,
            // quel jour) a enregistré cette vente. Nullable pour les ventes
            // en ligne, qui ne passent par aucune session de caisse.
            $table->foreignId('caisse_session_id')->nullable()->constrained()->restrictOnDelete();

            $table->integer('quantite')->default(0);
            $table->decimal('montant_total_ht_vente', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['canal_vente', 'magasin_id']);
            $table->index('caisse_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventes');
    }
};
