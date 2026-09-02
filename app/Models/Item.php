<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $guarded = [];

    public function roomInventories(): HasMany
    {
        return $this->hasMany(RoomInventory::class);
    }
}
