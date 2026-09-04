<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoomResource;
use App\Models\Faculty;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoomController extends Controller
{
   public function index()
    {
        $rooms = Room::with(['faculty'])
            ->withCount('inventories')
            ->latest()
            ->paginate(15)
            ->through(fn(Room $room) => (new RoomResource($room))->resolve());

        return Inertia::render('Admin/MasterData/Faculties/Rooms/Index', [
            'rooms' => $rooms,
            'faculties' => Faculty::get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'code' => 'required|string|max:50|unique:rooms,code',
            'name' => 'required|string|max:255',
            'building' => 'nullable|string|max:255',
            'floor' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        Room::create($validated);

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Ruangan berhasil ditambahkan',
        ]);
    }

    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'code' => 'required|string|max:50|unique:rooms,code,' . $room->id,
            'name' => 'required|string|max:255',
            'building' => 'nullable|string|max:255',
            'floor' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $room->update($validated);

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Ruangan berhasil diperbarui',
        ]);
    }

    public function destroy(Room $room)
    {
        // Proteksi jika ruangan masih punya relasi barang atau pengadaan
        if ($room->inventories()->exists() || $room->procurements()->exists()) {
            return redirect()->back()->with('toast', [
                'type' => 'error',
                'message' => 'Ruangan tidak dapat dihapus karena masih terkait dengan inventaris atau pengadaan',
            ]);
        }

        $room->delete();

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Ruangan berhasil dihapus',
        ]);
    }
}
