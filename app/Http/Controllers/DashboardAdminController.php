<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardAdminController extends Controller
{
    /**
     * Dashboard Admin.
     */
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

        return Inertia::render('Admin/Dashboard', [
            'roomTypes' => $roomTypes,
        ]);
    }

    /**
     * Update satu Jenis Ruangan langsung dari dashboard.
     */
    public function update(Request $request, RoomType $roomType)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
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
        ]);

        $roomType->update($validated);

        return redirect()
            ->back()
            ->with('toast', [
                'type' => 'success',
                'message' => 'Jenis ruangan berhasil diperbarui.',
            ]);
    }

    /**
     * Hapus satu Jenis Ruangan.
     */
    public function destroy(RoomType $roomType)
    {
        // Jangan hapus jika masih digunakan oleh ruangan.
        if ($roomType->rooms()->exists()) {
            return redirect()
                ->back()
                ->with('toast', [
                    'type' => 'error',
                    'message' => 'Jenis ruangan tidak dapat dihapus karena masih digunakan oleh ruangan.',
                ]);
        }

        $roomType->delete();

        return redirect()
            ->back()
            ->with('toast', [
                'type' => 'success',
                'message' => 'Jenis ruangan berhasil dihapus.',
            ]);
    }
}