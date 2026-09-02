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
        Schema::create('procurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculties')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade'); // Admin Fakultas
            $table->foreignId('room_id')->nullable()->constrained('rooms')->onDelete('cascade');
            $table->string('item_name');
            $table->integer('quantity');
            $table->enum('type', ['replacement', 'new_item']); // Penggantian barang rusak / Barang baru
            $table->text('reason');

            // TTD Digital Pengaju (Admin Fakultas)
            $table->string('requester_signature')->nullable();
            $table->timestamp('requested_at')->nullable();

            // Status & Persetujuan Kepala Inventaris (Super Admin)
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->string('approver_signature')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('procurements');
    }
};
