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
        Schema::create('room_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->string('asset_code')->unique(); // Kode Unik Aset (e.g. FT-LAB1-MEJA-001)
            $table->enum('condition', ['good', 'damaged_light', 'damaged_heavy'])->default('good');
            $table->boolean('is_borrowable')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('room_inventories');
    }
};
