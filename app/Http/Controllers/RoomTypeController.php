<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomTypeController extends Controller
{
    public function index(): Response
    {
        $roomTypes = RoomType::query()
            ->withCount('rooms')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
                'description',
            ]);

        return Inertia::render('Admin/Inventory/Rooms/Index', [
            'roomTypes' => $roomTypes,
        ]);
    }

    /**
     * Menambahkan jenis ruangan.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:room_types,name',
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                'unique:room_types,slug',
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ], [
            'name.required' => 'Nama jenis ruangan wajib diisi.',
            'name.unique' => 'Nama jenis ruangan sudah digunakan.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.unique' => 'Slug sudah digunakan.',
        ]);

        RoomType::create($validated);

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => "Berhasil menambahkan jenis ruangan {$validated['name']}",
        ]);
    }

    /**
     * Memperbarui jenis ruangan.
     */
    public function update(
        Request $request,
        RoomType $roomType
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:room_types,name,' . $roomType->id,
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                'unique:room_types,slug,' . $roomType->id,
            ],
            'description' => [
                'nullable',
                'string',
            ],
        ], [
            'name.required' => 'Nama jenis ruangan wajib diisi.',
            'name.unique' => 'Nama jenis ruangan sudah digunakan.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.unique' => 'Slug sudah digunakan.',
        ]);

        $roomType->update($validated);

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => "Berhasil memperbarui jenis ruangan {$validated['name']}",
        ]);
    }

    /**
     * Menghapus jenis ruangan.
     */
    public function destroy(RoomType $roomType): RedirectResponse
    {
        /*
         * Jangan hapus jika masih digunakan oleh rooms.
         */
        if ($roomType->rooms()->exists()) {
            return redirect()->back()->with('toast', [
                'type' => 'error',
                'message' => "Jenis ruangan {$roomType->name} tidak dapat dihapus karena masih digunakan oleh data ruangan.",
            ]);
        }

        try {
            $name = $roomType->name;

            $roomType->delete();

            return redirect()->back()->with('toast', [
                'type' => 'success',
                'message' => "Berhasil menghapus jenis ruangan {$name}",
            ]);
        } catch (QueryException $e) {
            return redirect()->back()->with('toast', [
                'type' => 'error',
                'message' => 'Jenis ruangan tidak dapat dihapus karena masih digunakan oleh data lain.',
            ]);
        }
    }
}
