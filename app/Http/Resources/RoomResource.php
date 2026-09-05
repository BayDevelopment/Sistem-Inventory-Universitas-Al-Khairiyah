<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'faculty_id' => $this->faculty_id,

            'code' => $this->code,

            'name' => $this->name,

            'type' => $this->type,

            'building' => $this->building,

            'floor' => $this->floor,

            'building_floor' => $this->building_floor,

            'description' => $this->description,

            'is_active' => $this->is_active,

            'faculty' => $this->whenLoaded('faculty', function () {
                return [
                    'id' => $this->faculty->id,
                    'code' => $this->faculty->code,
                    'name' => $this->faculty->name,
                ];
            }),

            'inventories_count' => $this->inventories_count ?? 0,

            'roomInventories' => $this->whenLoaded(
                'inventories',
                function () {
                    return $this->inventories->map(
                        function ($inventory) {
                            return [
                                'id' => $inventory->id,

                                'room_id' => $inventory->room_id,

                                'item_id' => $inventory->item_id,

                                'item' => $inventory->relationLoaded('item')
                                    && $inventory->item
                                    ? [
                                        'id' => $inventory->item->id,
                                        'name' => $inventory->item->name,
                                    ]
                                    : null,

                                'asset_code' => $inventory->asset_code,

                                'condition' => $inventory->condition,

                                'is_borrowable' => $inventory->is_borrowable,

                                'notes' => $inventory->notes,
                            ];
                        }
                    );
                }
            ),
        ];
    }
}
