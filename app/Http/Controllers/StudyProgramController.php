<?php

namespace App\Http\Controllers;

use App\Models\StudyProgram;
use Illuminate\Http\Request;

class StudyProgramController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'code' => 'required|string|unique:study_programs,code',
            'degree' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'head_of_program' => 'required|string|max:255',
        ]);

        StudyProgram::create($validated);

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Program studi berhasil ditambahkan',
        ]);
    }

    public function update(Request $request, StudyProgram $studyProgram)
    {
        $validated = $request->validate([
            'faculty_id' => 'required|exists:faculties,id',
            'code' => 'required|string|unique:study_programs,code,' . $studyProgram->id,
            'degree' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'head_of_program' => 'required|string|max:255',
        ]);

        $studyProgram->update($validated);

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Program studi berhasil diperbarui',
        ]);
    }

    public function destroy(StudyProgram $studyProgram)
    {
        $studyProgram->delete();

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Program studi berhasil dihapus',
        ]);
    }
}
