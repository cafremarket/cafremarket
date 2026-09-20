<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryDetailResource;
use App\Http\Resources\CategoryLightResource;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;

/**
 * Categories are fully admin-managed — a store can only browse the
 * published list to pick one when creating/editing a product. No
 * create/update/delete is exposed here.
 */
class CategoryController extends Controller
{
    /**
     * List of top-level Categories (for a "parent category" filter/picker).
     */
    public function topCategories()
    {
        return CategoryResource::collection(
            Category::active()->with(['coverImage', 'featureImage'])->orderBy('name')->get()
        );
    }

    /**
     * Display a listing of the resource (sub-categories).
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $categories = SubCategory::active()
            ->with(['coverImage', 'featureImage', 'category'])
            ->orderBy('id', 'asc');

        if ($request->has('category_id')) {
            $categories->where('category_id', $request->get('category_id'));
        }

        $categories = $categories->paginate();

        return CategoryLightResource::collection($categories);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(SubCategory $category)
    {
        return new CategoryDetailResource($category);
    }
}
