<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * L'enum d'origine ('entree', 'ajustement') a un CHECK constraint SQLite
     * qui bloque 'sortie' (utilisé par StockMouvement::enregistrerSortie()).
     * On passe la colonne en string classique : le contrôle des valeurs
     * autorisées se fait désormais côté modèle (constantes TYPE_*), ce qui
     * évite une migration à chaque ajout futur de type.
     */
    public function up(): void
    {
        Schema::table('stock_mouvements', function (Blueprint $table) {
            $table->string('type')->default('entree')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_mouvements', function (Blueprint $table) {
            $table->enum('type', ['entree', 'ajustement'])->default('entree')->change();
        });
    }
};
