<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Contoh: Meja, Kursi, Papan Tulis, Proyektor
            $table->string('category')->nullable(); // Mebel, Elektronik, ATK
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('items');
    }
};
