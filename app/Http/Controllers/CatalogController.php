<?php

namespace App\Http\Controllers;

use App\Models\RoomInventory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    /**
     * Display borrowable inventory catalog.
     *
     * Inventory can be borrowed across faculties.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $facultyId = $request->input('faculty_id');
        $condition = $request->input('condition');
        $sort = $request->input('sort', 'asset_asc');

        $query = RoomInventory::query()
            ->with([
                'item:id,name',

                'room:id,name,code,faculty_id,is_active',

                'room.faculty:id,name,code',
            ])
            ->where('is_borrowable', true)
            ->where('condition', '!=', 'damaged_heavy')
            ->whereHas('room', function ($query) {
                $query->where('is_active', true);
            });

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query
                    ->where('asset_code', 'like', "%{$search}%")

                    ->orWhereHas('item', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    })

                    ->orWhereHas('room', function ($query) use ($search) {
                        $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%")
                            ->orWhereHas('faculty', function ($query) use ($search) {
                                $query
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            });
                    });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Faculty filter
        |--------------------------------------------------------------------------
        */
        if ($facultyId !== null && $facultyId !== '') {
            $query->whereHas('room', function ($query) use ($facultyId) {
                $query->where('faculty_id', $facultyId);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Condition filter
        |--------------------------------------------------------------------------
        */
        if (
            $condition !== null &&
            $condition !== '' &&
            in_array($condition, ['good', 'damaged_light'], true)
        ) {
            $query->where('condition', $condition);
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */
        match ($sort) {
            'asset_desc' => $query->orderByDesc('asset_code'),

            'item_asc' => $query
                ->leftJoin('items', 'room_inventories.item_id', '=', 'items.id')
                ->select('room_inventories.*')
                ->orderBy('items.name'),

            'item_desc' => $query
                ->leftJoin('items', 'room_inventories.item_id', '=', 'items.id')
                ->select('room_inventories.*')
                ->orderByDesc('items.name'),

            default => $query->orderBy('asset_code'),
        };

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        |
        | 24 records per page.
        |
        */
        $roomInventories = $query
            ->paginate(24)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Faculty filter options
        |--------------------------------------------------------------------------
        |
        | Ambil daftar fakultas saja, bukan seluruh inventory.
        |
        */
        $faculties = \App\Models\Faculty::query()
            ->select([
                'id',
                'name',
                'code',
            ])
            ->whereHas('rooms', function ($query) {
                $query
                    ->where('is_active', true)
                    ->whereHas('inventories', function ($query) {
                        $query
                            ->where('is_borrowable', true)
                            ->where('condition', '!=', 'damaged_heavy');
                    });
            })
            ->orderBy('name')
            ->get();

        return Inertia::render('Users/Katalog/Index', [
            'roomInventories' => $roomInventories,
            'faculties' => $faculties,

            'filters' => [
                'search' => $search,
                'faculty_id' => $facultyId,
                'condition' => $condition,
                'sort' => $sort,
            ],
        ]);
    }
}