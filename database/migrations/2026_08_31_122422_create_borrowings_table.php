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
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('faculty_id')->constrained('faculties')->onDelete('cascade');
            $table->foreignId('room_inventory_id')->constrained('room_inventories')->onDelete('cascade');
            $table->dateTime('borrow_date');
            $table->dateTime('expected_return_date');
            $table->dateTime('actual_return_date')->nullable();
            $table->text('purpose');

            // TTD Digital Pemohon (Canvas Pad / Image Base64 Upload)
            $table->string('applicant_signature')->nullable();
            $table->timestamp('signed_at')->nullable();

            // Status & Persetujuan Admin Fakultas
            $table->enum('status', ['pending', 'approved', 'rejected', 'borrowed', 'returned'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->string('approver_signature')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('borrowings');
    }
};
