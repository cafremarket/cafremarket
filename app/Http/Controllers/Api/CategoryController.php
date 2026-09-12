<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryGroupResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategorySubGroupResource;
use App\Models\Category;
use App\Models\CategoryGroup;
use App\Models\CategorySubGroup;
use App\Http\Controllers\Api\Concerns\CachesApiResponses;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use CachesApiResponses;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $sub_group = null)
    {
        return $this->rememberApi('categories:'.($sub_group ?: 'all'), function () use ($sub_group) {
            $categories = Category::active();

            if ($sub_group) {
                $categories = $categories->where('category_sub_group_id', $sub_group);
            }

            $categories = $categories->with(['coverImage', 'featureImage'])
                ->orderBy('order', 'asc')->get();

            return CategoryResource::collection($categories);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function categoryGroup()
    {
        return $this->rememberApi('category-groups', function () {
            $categories = CategoryGroup::with(['coverImage', 'logoImage'])
                ->orderBy('order', 'asc')
                ->active()->get();

            return CategoryGroupResource::collection($categories);
        });
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function categorySubGroup(Request $request, $group = null)
    {
        return $this->rememberApi('category-subgroups:'.($group ?: 'all'), function () use ($group) {
            $categories = CategorySubGroup::active();

            if ($group) {
                $categories = $categories->where('category_group_id', $group);
            }

            $categories = $categories->with(['coverImage'])
                ->orderBy('order', 'asc')
                ->get();

            return CategorySubGroupResource::collection($categories);
        });
    }

    public function featuredCategories()
    {
        return CategoryResource::collection(collect([]));
    }

    /**
     * Leaf categories under a category group (subgroups flattened — matches storefront browse).
     */
    public function categoriesOfGroup($group)
    {
        return $this->rememberApi('categories-of-group:'.$group, function () use ($group) {
            $subGroupIds = CategorySubGroup::query()
                ->where('category_group_id', $group)
                ->pluck('id');

            $categories = Category::active()
                ->whereIn('category_sub_group_id', $subGroupIds)
                ->with(['coverImage', 'featureImage'])
                ->orderBy('order', 'asc')
                ->get();

            return CategoryResource::collection($categories);
        });
    }

    public function trendingCategories()
    {
        return CategoryResource::collection(collect([]));
    }
}
