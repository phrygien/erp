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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code');
            $table->foreignId('category_id')->constrained();
            $table->foreignId('marque_id')->constrained();
            $table->foreignId('type_id')->constrained();
            $table->foreignId('ligne_id')->constrained();
            $table->string('designation');
            $table->string('designation_variant');
            $table->string('article');
            $table->string('ref_fabri_n_1');
            $table->string('EAN')->unique();
            $table->decimal('pght_parkod', 10, 2);
            $table->integer('tva');
            $table->string('devise')->default('EUR');
            $table->string('hs_code');
            $table->string('statut_parkod');
            $table->enum('state', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
