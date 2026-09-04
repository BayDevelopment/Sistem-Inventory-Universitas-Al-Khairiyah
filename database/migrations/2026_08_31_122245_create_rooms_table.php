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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculties')->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['kelas', 'lab_komputer', 'ruang_dosen', 'ruang_akademik'])->default('kelas');
            $table->string('building')->nullable();
            $table->string('floor')->nullable();
            $table->string('building_floor')->nullable(); // Cadangan jika masih dipakai di frontend/view lama
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
