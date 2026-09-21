<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\SubCategoryResource;
use App\Models\Category;
use App\Models\SubCategory;
use App\Http\Controllers\Api\Concerns\CachesApiResponses;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use CachesApiResponses;

    /**
     * Display a listing of the resource (sub-categories, optionally filtered
     * by their parent top-level category).
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $category = null)
    {
        return $this->rememberApi('categories:'.($category ?: 'all'), function () use ($category) {
            $categories = SubCategory::active();

            if ($category) {
                $categories = $categories->where('category_id', $category);
            }

            $categories = $categories->with(['coverImage', 'featureImage', 'category'])
                ->orderBy('name', 'asc')->get();

            return SubCategoryResource::collection($categories);
        });
    }

    /**
     * Display a listing of the resource (top-level categories).
     *
     * @return \Illuminate\Http\Response
     */
    public function categorySubGroup()
    {
        return $this->rememberApi('category-subgroups', function () {
            $categories = Category::with(['coverImage', 'featureImage'])
                ->orderBy('name', 'asc')
                ->active()->get();

            return CategoryResource::collection($categories);
        });
    }

    /**
     * Curated, admin-flagged categories for the homepage.
     *
     * @return \Illuminate\Http\Response
     */
    public function featuredCategories()
    {
        return $this->rememberApi('featured-categories', function () {
            $categories = Category::active()->featured()
                ->with(['coverImage', 'featureImage'])
                ->orderBy('name', 'asc')
                ->get();

            return CategoryResource::collection($categories);
        });
    }

    public function trendingCategories()
    {
        return CategoryResource::collection(collect([]));
    }
}
