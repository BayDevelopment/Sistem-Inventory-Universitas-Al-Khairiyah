<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculties')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');

            $table->string('item_name');
            $table->integer('quantity');
            $table->enum('type', ['replacement', 'new_item']);
            $table->text('reason');
            $table->string('subject')->nullable(); // perihal, opsional override

            /*
            |--------------------------------------------------------------------------
            | Lampiran
            |--------------------------------------------------------------------------
            |
            | Disimpan sebagai array path (JSON), bukan tabel terpisah.
            | Kosong/null = tidak ada lampiran, ditampilkan "-" di PDF.
            | Cukup untuk kebutuhan saat ini; jika ke depan butuh
            | metadata lebih rumit (kategori lampiran, uploader,
            | tanggal per-file, dsb), baru dipertimbangkan tabel
            | terpisah.
            |--------------------------------------------------------------------------
            */
            $table->json('attachments')->nullable();

            $table->string('requester_signature')->nullable();
            $table->timestamp('requested_at')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->string('document_number')->nullable()->unique(); // nomor surat, diisi saat approved
            $table->foreignId('processed_by')->nullable()->constrained('users');
            $table->string('approver_signature')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('admin_note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurements');
    }
};