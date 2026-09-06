<?php

namespace App\Http\Controllers;

use App\Http\Resources\FacultyResource;
use App\Models\Faculty;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FacultyController extends Controller
{
    public function index()
    {
        $faculties = Faculty::with('studyPrograms')
            ->latest()
            ->paginate(15)
            ->through(
                fn(Faculty $faculty) => (new FacultyResource($faculty))->resolve()
            );

        $roomTypes = RoomType::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'slug',
                'description',
            ]);

        return Inertia::render('Admin/MasterData/Faculties/Index', [
            'faculties' => $faculties,
            'roomTypes' => $roomTypes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:faculties,code',
            'name' => 'required|string|max:255',
            'dean' => 'required|string|max:255',
        ]);

        Faculty::create($validated);

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Fakultas berhasil ditambahkan',
        ]);
    }

    public function update(Request $request, Faculty $faculty)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:faculties,code,' . $faculty->id,
            'name' => 'required|string|max:255',
            'dean' => 'required|string|max:255',
        ]);

        $faculty->update($validated);

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Fakultas berhasil diperbarui',
        ]);
    }

    public function destroy(Faculty $faculty)
    {
        $faculty->delete();

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Fakultas berhasil dihapus',
        ]);
    }
}
