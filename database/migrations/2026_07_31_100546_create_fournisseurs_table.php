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
        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->text('raison_social')->nullable();
            $table->text('adresse_siege');
            $table->string('code_postal');
            $table->string('ville');
            $table->string('telephone')->unique();
            $table->string('fax')->nullable();
            $table->string('email')->unique();
            $table->string('adresse_retour')->nullable();
            $table->string('code_postal_retour')->nullable();
            $table->string('ville_retour')->nullable();
            $table->enum('state', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fournisseurs');
    }
};
