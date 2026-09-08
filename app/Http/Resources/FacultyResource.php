<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FacultyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'dean' => $this->dean,
            'dean_nip' => $this->dean_nip,

            // File paths
            'letterhead_path' => $this->letterhead_path,
            'dean_signature' => $this->dean_signature,

            'studyPrograms' => $this->whenLoaded(
                'studyPrograms',
                fn() => $this->studyPrograms
                    ->map(fn($program) => [
                        'id' => $program->id,
                        'faculty_id' => $program->faculty_id,
                        'code' => $program->code,
                        'degree' => $program->degree,
                        'name' => $program->name,
                        'head_of_program' => $program->head_of_program,
                    ])
                    ->values()
                    ->all()
            ),
        ];
    }
}