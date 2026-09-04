<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomInventoryResource extends JsonResource
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
            'room_id' => $this->room_id,
            'item_id' => $this->item_id,
            'asset_code' => $this->asset_code,
            'condition' => $this->condition,
            'is_borrowable' => $this->is_borrowable,
            'notes' => $this->notes,
            'room' => new RoomResource($this->whenLoaded('room')),
            'item' => $this->whenLoaded('item'),
            'borrowings_count' => $this->whenCounted('borrowings'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
