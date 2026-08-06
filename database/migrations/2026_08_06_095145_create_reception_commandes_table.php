<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reception_commandes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_reception')->unique();
            $table->foreignId('commande_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bon_commande_id')->nullable()->constrained();
            $table->string('numero_bl')->nullable()->comment('Bon de livraison fournisseur');
            $table->date('date_reception');
            $table->enum('statut', ['en_cours', 'partielle', 'complete', 'annulee'])
                ->default('en_cours');
            $table->text('commentaire')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['commande_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reception_commandes');
    }
};
