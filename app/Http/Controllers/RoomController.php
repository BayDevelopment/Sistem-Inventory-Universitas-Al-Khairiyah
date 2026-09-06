<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoomResource;
use App\Models\Faculty;
use App\Models\Item;
use App\Models\Room;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoomController extends Controller
{
    public function index()
    {
        /*
    |--------------------------------------------------------------------------
    | Rooms untuk tabel
    |--------------------------------------------------------------------------
    */

        $rooms = Room::with([
            'faculty',
            'roomType',
            'inventories.item',
        ])
            ->withCount('inventories')
            ->latest()
            ->paginate(15)
            ->through(
                fn(Room $room) => (new RoomResource($room))->resolve()
            );

        /*
    |--------------------------------------------------------------------------
    | Semua Rooms untuk dropdown inventaris
    |--------------------------------------------------------------------------
    */

        $allRooms = Room::query()
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'name',
            ]);

        /*
    |--------------------------------------------------------------------------
    | Master Barang
    |--------------------------------------------------------------------------
    */

        $items = Item::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        /*
    |--------------------------------------------------------------------------
    | Master Tipe Ruangan
    |--------------------------------------------------------------------------
    */

        $roomTypes = \App\Models\RoomType::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
            ]);

        /*
    |--------------------------------------------------------------------------
    | Fakultas
    |--------------------------------------------------------------------------
    */

        $faculties = Faculty::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
            ]);


        return Inertia::render(
            'Admin/MasterData/Faculties/Rooms/Index',
            [
                'rooms' => $rooms,
                'allRooms' => $allRooms,
                'items' => $items,
                'roomTypes' => $roomTypes,
                'faculties' => $faculties,
            ]
        );
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'faculty_id' => [
                'required',
                'exists:faculties,id',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'unique:rooms,code',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'room_type_id' => [
                'required',
                'exists:room_types,id',
            ],

            'building' => [
                'nullable',
                'string',
                'max:255',
            ],

            'floor' => [
                'nullable',
                'string',
                'max:50',
            ],

            'building_floor' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'boolean',
            ],
        ]);


        $validated['building_floor'] = $this->buildBuildingFloor(
            $validated['building'] ?? null,
            $validated['floor'] ?? null,
            $validated['building_floor'] ?? null
        );

        Room::create($validated);

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Ruangan berhasil ditambahkan',
        ]);
    }

    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'faculty_id' => [
                'required',
                'exists:faculties,id',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'unique:rooms,code,' . $room->id,
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'room_type_id' => [
                'required',
                'exists:room_types,id',
            ],

            'building' => [
                'nullable',
                'string',
                'max:255',
            ],

            'floor' => [
                'nullable',
                'string',
                'max:50',
            ],

            'building_floor' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'boolean',
            ],
        ]);

        $validated['building_floor'] = $this->buildBuildingFloor(
            $validated['building'] ?? null,
            $validated['floor'] ?? null,
            $validated['building_floor'] ?? null
        );

        $room->update($validated);

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Ruangan berhasil diperbarui',
        ]);
    }

    public function destroy(Room $room)
    {
        // Jangan hapus ruangan yang masih digunakan inventaris
        // atau memiliki data pengadaan.
        if (
            $room->inventories()->exists() ||
            $room->procurements()->exists()
        ) {
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


    private function buildBuildingFloor(
        ?string $building,
        ?string $floor,
        ?string $fallback = null
    ): ?string {
        $building = trim((string) $building);
        $floor = trim((string) $floor);

        if ($building !== '' && $floor !== '') {
            return $building . ' - ' . $floor;
        }

        if ($building !== '') {
            return $building;
        }

        if ($floor !== '') {
            return $floor;
        }

        return $fallback ?: null;
    }
}
