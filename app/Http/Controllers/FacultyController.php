<?php

namespace App\Http\Controllers;

use App\Http\Resources\FacultyResource;
use App\Models\Faculty;
use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FacultyController extends Controller
{
    /**
     * Display faculty list.
     */
    public function index()
    {
        $faculties = Faculty::with('studyPrograms')
            ->latest()
            ->paginate(15)
            ->through(
                fn (Faculty $faculty) => (new FacultyResource($faculty))->resolve()
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

    /**
     * Store a newly created faculty.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                'unique:faculties,code',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'dean' => [
                'required',
                'string',
                'max:255',
            ],

            'dean_nip' => [
                'nullable',
                'string',
                'max:50',
            ],

            'letterhead' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],

            'dean_signature' => [
                'nullable',
                'image',
                'mimes:png',
                'max:1024',
            ],
        ]);

        $data = [
            'code' => $validated['code'],
            'name' => $validated['name'],
            'dean' => $validated['dean'],
            'dean_nip' => $validated['dean_nip'] ?? null,
        ];

        if ($request->hasFile('letterhead')) {
            $data['letterhead_path'] = $this->storeFacultyFile(
                $request->file('letterhead'),
                'letterheads'
            );
        }

        if ($request->hasFile('dean_signature')) {
            $data['dean_signature'] = $this->storeFacultyFile(
                $request->file('dean_signature'),
                'signatures/deans'
            );
        }

        Faculty::create($data);

        return redirect()
            ->back()
            ->with('toast', [
                'type' => 'success',
                'message' => 'Fakultas berhasil ditambahkan',
            ]);
    }

    /**
     * Update the specified faculty.
     */
    public function update(Request $request, Faculty $faculty)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('faculties', 'code')
                    ->ignore($faculty->id),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'dean' => [
                'required',
                'string',
                'max:255',
            ],

            'dean_nip' => [
                'nullable',
                'string',
                'max:50',
            ],

            'letterhead' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048',
            ],

            'dean_signature' => [
                'nullable',
                'image',
                'mimes:png',
                'max:1024',
            ],

            'remove_letterhead' => [
                'nullable',
                'boolean',
            ],

            'remove_dean_signature' => [
                'nullable',
                'boolean',
            ],
        ]);

        $data = [
            'code' => $validated['code'],
            'name' => $validated['name'],
            'dean' => $validated['dean'],
            'dean_nip' => $validated['dean_nip'] ?? null,
        ];

        /*
        |--------------------------------------------------------------------------
        | Kop Surat
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('letterhead')) {
            // Hapus file lama terlebih dahulu.
            $this->deleteFacultyFile($faculty->letterhead_path);

            // Simpan file baru.
            $data['letterhead_path'] = $this->storeFacultyFile(
                $request->file('letterhead'),
                'letterheads'
            );
        } elseif ($request->boolean('remove_letterhead')) {
            // User memilih hapus kop surat tanpa upload baru.
            $this->deleteFacultyFile($faculty->letterhead_path);

            $data['letterhead_path'] = null;
        }

        /*
        |--------------------------------------------------------------------------
        | Tanda Tangan Dekan
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('dean_signature')) {
            // Hapus TTD lama terlebih dahulu.
            $this->deleteFacultyFile($faculty->dean_signature);

            // Simpan TTD baru.
            $data['dean_signature'] = $this->storeFacultyFile(
                $request->file('dean_signature'),
                'signatures/deans'
            );
        } elseif ($request->boolean('remove_dean_signature')) {
            // User memilih hapus TTD tanpa upload baru.
            $this->deleteFacultyFile($faculty->dean_signature);

            $data['dean_signature'] = null;
        }

        $faculty->update($data);

        return redirect()
            ->back()
            ->with('toast', [
                'type' => 'success',
                'message' => 'Fakultas berhasil diperbarui',
            ]);
    }

    /**
     * Remove the specified faculty.
     */
    public function destroy(Faculty $faculty)
    {
        // Hapus file kop surat.
        $this->deleteFacultyFile($faculty->letterhead_path);

        // Hapus file tanda tangan Dekan.
        $this->deleteFacultyFile($faculty->dean_signature);

        // Hapus data fakultas.
        $faculty->delete();

        return redirect()
            ->back()
            ->with('toast', [
                'type' => 'success',
                'message' => 'Fakultas berhasil dihapus',
            ]);
    }

    /**
     * Store uploaded faculty file on public disk.
     */
    private function storeFacultyFile(
        UploadedFile $file,
        string $directory
    ): string {
        $filename = Str::uuid()->toString()
            . '.'
            . $file->getClientOriginalExtension();

        return $file->storeAs(
            $directory,
            $filename,
            'public'
        );
    }

    /**
     * Delete faculty file from public disk.
     */
    private function deleteFacultyFile(?string $path): void
    {
        if (!$path) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}