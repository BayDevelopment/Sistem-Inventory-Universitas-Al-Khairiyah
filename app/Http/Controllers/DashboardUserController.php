<?php

namespace App\Http\Controllers;

use App\Models\Borrowing;
use App\Models\RoomInventory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardUserController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $borrowings = Borrowing::query()
            ->with([
                'roomInventory.room:id,name,code,faculty_id',
                'roomInventory.item:id,name',
            ])
            ->where('user_id', $user->id)
            ->latest('id')
            ->get();

        $availableAssetQuery = RoomInventory::query()
            ->where('is_borrowable', true)
            ->where('condition', '!=', 'damaged_heavy')
            ->whereHas('room', function ($query) {
                $query->where('is_active', true);
            })
            ->whereDoesntHave('borrowings', function ($query) {
                $query->whereIn('status', [
                    'pending',
                    'approved',
                    'borrowed',
                ]);
            });

        $availableAssetCount = (clone $availableAssetQuery)->count();

        $roomInventories = $availableAssetQuery
            ->with([
                'room:id,name,code,faculty_id',
                'item:id,name',
            ])
            ->orderBy('asset_code')
            ->limit(3)
            ->get([
                'id',
                'room_id',
                'item_id',
                'asset_code',
                'condition',
                'is_borrowable',
            ]);

        return Inertia::render('Users/Dashboard', [
            'borrowings' => $borrowings,
            'roomInventories' => $roomInventories,
            'availableAssetCount' => $availableAssetCount,
        ]);
    }
}
