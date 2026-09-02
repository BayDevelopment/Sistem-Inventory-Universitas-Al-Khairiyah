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
        Schema::table('users', function (Blueprint $table) {
            $table->string('identity_number')->nullable()->unique()->after('name'); // NIM / NIP / NIDN
            $table->enum('role', [
                'super_admin',     // Kepala Inventaris Universitas
                'admin_fakultas',  // Petugas Inventaris Fakultas
                'dosen',
                'mahasiswa',
                'sdm'
            ])->default('mahasiswa')->after('email');
            $table->foreignId('faculty_id')->nullable()->constrained('faculties')->onDelete('cascade')->after('role');
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['faculty_id']);
            $table->dropColumn(['identity_number', 'role', 'faculty_id']);
        });
    }
};
