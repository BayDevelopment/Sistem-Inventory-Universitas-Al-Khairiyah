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
        Schema::create('faculties', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Contoh: Fakultas Teknik
            $table->string('code')->unique(); // Contoh: FT
            $table->string('dean'); 
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('faculties');
    }
};
