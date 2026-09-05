<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    public function scopeOfCategory(
        Builder $query,
        int $categoryId
    ): Builder {
        return $query->where('category_id', $categoryId);
    }

    /*
    |--------------------------------------------------------------------------
    | Category Helpers (kategori masih berupa kolom string, bukan tabel relasi)
    |--------------------------------------------------------------------------
    */

    public static function listCategories(): Collection
    {
        return static::categorized()
            ->groupBy('category')
            ->selectRaw('category, count(*) as items_count')
            ->orderBy('category')
            ->get();
    }

    public static function renameCategory(string $from, string $to): int
    {
        return static::ofCategory($from)->update(['category_id' => $to]);
    }

    public static function clearCategory(string $category): int
    {
        return static::ofCategory($category)->update(['category_id' => null]);
    }
}
