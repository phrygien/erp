<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repartition_detailcommande_historiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repartition_detailcommande_id')
                ->constrained('repartition_detailcommandes')
                ->cascadeOnDelete();
            $table->string('champ');
            $table->text('ancienne_valeur')->nullable();
            $table->text('nouvelle_valeur')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['repartition_detailcommande_id', 'champ']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repartition_detailcommande_historiques');
    }
};
