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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculties')->onDelete('cascade');
            $table->string('name'); // Contoh: Lab Komputer 1, Ruang 302
            $table->enum('type', ['kelas', 'lab_komputer', 'ruang_dosen', 'ruang_akademik']);
            $table->string('building_floor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('rooms');
    }
};
