<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RoomInventory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_borrowable' => 'boolean',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(
            Borrowing::class,
            'room_inventory_id'
        );
    }

    protected static function booted()
    {
        static::creating(function ($inventory) {
            // Jika asset_code belum diisi manual, buat otomatis
            if (empty($inventory->asset_code)) {
                $room = Room::find($inventory->room_id);
                $item = Item::find($inventory->item_id);

                if ($room && $item) {
                    // Bersihkan string atau ambil slug ringkas (contoh: KODE-RUANGAN dan NAMA-BARANG)
                    $roomCode = strtoupper($room->code);

                    // Ambil inisial/slug nama barang yang bersih (misal: "Meja Kayu" -> "MEJA-KAYU")
                    $itemName = strtoupper(Str::slug($item->name, '-'));

                    // Cari jumlah aset yang sudah ada dengan ruangan & barang yang sama untuk nomor urut
                    $lastCount = self::where('room_id', $inventory->room_id)
                        ->where('item_id', $inventory->item_id)
                        ->count();

                    $nextNumber = str_pad($lastCount + 1, 3, '0', STR_PAD_LEFT);

                    // Hasil format: KODE-RUANGAN-NAMA-BARANG-001 (Contoh: LAB-01-MEJA-001)
                    $inventory->asset_code = "{$roomCode}-{$itemName}-{$nextNumber}";
                }
            }
        });
    }
}
