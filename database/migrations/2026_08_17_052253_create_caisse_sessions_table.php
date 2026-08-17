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
        Schema::create('caisse_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caisse_id')->constrained()->restrictOnDelete();
            $table->foreignId('responsable_id')->constrained('users')->restrictOnDelete();
            $table->date('date_session');
            $table->timestamp('ouverte_le');
            $table->timestamp('fermee_le')->nullable();
            $table->decimal('solde_ouverture', 10, 2)->default(0);
            $table->decimal('solde_cloture_theorique', 10, 2)->default(0);
            $table->decimal('solde_cloture_reel', 10, 2)->default(0);
            $table->decimal('ecart', 10, 2)->default(0);
            $table->enum('statut', ['ouverte', 'fermee'])->default('ouverte');
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->index(['caisse_id', 'statut']);
            $table->index(['caisse_id', 'date_session']);
            $table->index('responsable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caisse_sessions');
    }
};
