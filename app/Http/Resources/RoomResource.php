<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
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
            'faculty_id' => $this->faculty_id,
            'code' => $this->code,
            'name' => $this->name,
            'building' => $this->building,
            'floor' => $this->floor,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'faculty' => new FacultyResource($this->whenLoaded('faculty')),
            'inventories_count' => $this->whenCounted('inventories'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
