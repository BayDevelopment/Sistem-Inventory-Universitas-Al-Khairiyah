<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculties', function (Blueprint $table) {
            $table->id();

            $table->string('name'); // Contoh: Fakultas Ilmu Komputer
            $table->string('code')->unique(); // Contoh: FIK

            $table->string('letterhead_path')->nullable();

            $table->string('dean');
            $table->string('dean_nip')->nullable();
            $table->string('dean_signature')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculties');
    }
};
