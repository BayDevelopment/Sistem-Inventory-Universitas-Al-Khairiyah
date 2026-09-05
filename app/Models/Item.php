<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $guarded = [];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class);
    }

    public function roomInventories(): HasMany
    {
        return $this->hasMany(RoomInventory::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeCategorized(Builder $query): Builder
    {
        return $query->whereNotNull('category_id');
    }

    public function scopeOfCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }
}
