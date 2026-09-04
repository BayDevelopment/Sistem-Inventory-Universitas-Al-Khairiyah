<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoomInventoryResource;
use App\Models\Item;
use App\Models\Room;
use App\Models\RoomInventory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoomInventoryController extends Controller
{
    public function index()
    {
        $inventories = RoomInventory::with(['room', 'item'])
            ->withCount('borrowings')
            ->latest()
            ->paginate(15)
            ->through(fn(RoomInventory $inventory) => (new RoomInventoryResource($inventory))->resolve());

        return Inertia::render('Admin/RoomInventories/Index', [
            'inventories' => $inventories,
            'rooms' => Room::where('is_active', true)->get(['id', 'name', 'code']),
            'items' => Item::get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'item_id' => 'required|exists:items,id',
            'asset_code' => 'required|string|max:100|unique:room_inventories,asset_code',
            'condition' => 'required|in:good,damaged_light,damaged_heavy',
            'is_borrowable' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        RoomInventory::create($validated);

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Inventaris ruangan berhasil ditambahkan',
        ]);
    }

    public function update(Request $request, RoomInventory $roomInventory)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'item_id' => 'required|exists:items,id',
            'asset_code' => 'required|string|max:100|unique:room_inventories,asset_code,' . $roomInventory->id,
            'condition' => 'required|in:good,damaged_light,damaged_heavy',
            'is_borrowable' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $roomInventory->update($validated);

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Inventaris ruangan berhasil diperbarui',
        ]);
    }

    public function destroy(RoomInventory $roomInventory)
    {
        // Proteksi jika aset barang pernah/sedang memiliki riwayat peminjaman
        if ($roomInventory->borrowings()->exists()) {
            return redirect()->back()->with('toast', [
                'type' => 'error',
                'message' => 'Barang inventaris tidak dapat dihapus karena memiliki riwayat peminjaman',
            ]);
        }

        $roomInventory->delete();

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Inventaris ruangan berhasil dihapus',
        ]);
    }
}
