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

            'room_type_id' => $this->room_type_id,

            'code' => $this->code,

            'name' => $this->name,

            'building' => $this->building,

            'floor' => $this->floor,

            'building_floor' => $this->building_floor,

            'description' => $this->description,

            'is_active' => (bool) $this->is_active,


            'faculty' => $this->whenLoaded(
                'faculty',
                function () {
                    return [
                        'id' => $this->faculty->id,
                        'code' => $this->faculty->code,
                        'name' => $this->faculty->name,
                        'dean' => $this->faculty->dean ?? null,
                    ];
                }
            ),

            'room_type' => $this->whenLoaded(
                'roomType',
                function () {
                    return [
                        'id' => $this->roomType->id,
                        'name' => $this->roomType->name,
                        'slug' => $this->roomType->slug,
                    ];
                }
            ),

            'roomInventories' => $this->whenLoaded(
                'inventories',
                function () {
                    return $this->inventories->map(
                        function ($inventory) {
                            return [
                                'id' => $inventory->id,

                                'room_id' =>
                                $inventory->room_id,

                                'item_id' =>
                                $inventory->item_id,

                                'asset_code' =>
                                $inventory->asset_code,

                                'condition' =>
                                $inventory->condition,

                                'is_borrowable' =>
                                (bool) $inventory->is_borrowable,

                                'notes' =>
                                $inventory->notes,

                                'item' =>
                                $inventory->relationLoaded('item')
                                    && $inventory->item
                                    ? [
                                        'id' =>
                                        $inventory->item->id,

                                        'name' =>
                                        $inventory->item->name,
                                    ]
                                    : null,
                            ];
                        }
                    );
                }
            ),

            'inventories_count' =>
            $this->whenCounted(
                'inventories'
            ),
        ];
    }
}
