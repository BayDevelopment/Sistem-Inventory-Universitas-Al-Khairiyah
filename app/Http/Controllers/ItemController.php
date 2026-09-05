<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemRequest;
use App\Models\Item;
use App\Models\ItemCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    public function index(): Response
    {
        $items = Item::with('category')
            ->withCount('roomInventories')
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('Admin/Inventory/Items/Index', [
            'items' => $items,
            'categories' => ItemCategory::select(['id', 'code', 'name'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(ItemRequest $request): RedirectResponse
    {
        Item::create($request->validated());

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Barang berhasil ditambahkan.',
        ]);
    }

    public function update(
        ItemRequest $request,
        Item $item
    ): RedirectResponse {
        $item->update($request->validated());

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Barang berhasil diperbarui.',
        ]);
    }

    public function destroy(Item $item): RedirectResponse
    {
        $usageCount = $item->roomInventories()->count();

        if ($usageCount > 0) {
            return redirect()->back()->with('toast', [
                'type' => 'error',
                'message' =>
                "Barang ini masih dipakai di {$usageCount} aset inventaris. " .
                    "Hapus dulu aset yang memakainya sebelum menghapus barang ini.",
            ]);
        }

        $item->delete();

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Barang berhasil dihapus.',
        ]);
    }
}
