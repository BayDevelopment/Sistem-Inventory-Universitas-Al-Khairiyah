<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemCategoryRequest;
use App\Models\Item;
use App\Models\ItemCategory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ItemCategoryController extends Controller
{
    public function index(): Response
    {
        $categories = ItemCategory::query()
            ->withCount('items')
            ->orderBy('name')
            ->get();

        return Inertia::render(
            'Admin/MasterData/Faculties/Categories/Index',
            [
                'categories' => $categories,
            ]
        );
    }

    public function update(
        ItemCategoryRequest $request,
        ItemCategory $category
    ): RedirectResponse {
        $category->update($request->validated());

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Kategori berhasil diperbarui.',
        ]);
    }

    public function destroy(
        ItemCategory $category
    ): RedirectResponse {
        $category->delete();

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'Kategori berhasil dihapus.',
        ]);
    }
}
