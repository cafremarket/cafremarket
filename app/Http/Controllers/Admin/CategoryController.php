<?php

namespace App\Http\Controllers\Admin;

use App\Common\Authorizable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateCategoryRequest;
use App\Http\Requests\Validations\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\Category\CategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Yajra\DataTables\DataTables;

/**
 * Categories are fully platform/admin-managed — this controller (and its
 * views/routes) is only reachable by platform admins, never merchants.
 */
class CategoryController extends Controller
{
    use Authorizable;

    private $model_name;

    private $category;

    /**
     * construct
     */
    public function __construct(CategoryRepository $category)
    {
        parent::__construct();
        $this->model_name = trans('app.model.category');
        $this->category = $category;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        abort_unless(Auth::user()->isFromPlatform(), 403);

        $trashes = $this->category->trashOnly();

        return view('admin.category.index', compact('trashes'));
    }

    // Function will process the ajax request to fetch data
    public function getCategories(Request $request)
    {
        $category = Category::with(
            'featureImage',
            'coverImage',
            'translations',
        )->withCount(['subCategories']);

        $data = Datatables::of($category)
            ->editColumn('checkbox', function ($category) {
                return view('admin.category.partials.checkbox', compact('category'));
            })
            ->addColumn('option', function ($category) {
                return view('admin.category.partials.options', compact('category'));
            })
            ->editColumn('cover_image', function ($category) {
                return view('admin.category.partials.cover_image', compact('category'));
            })
            ->editColumn('feature_image', function ($category) {
                return view('admin.category.partials.feature_image', compact('category'));
            })
            ->editColumn('name', function ($category) {
                return view('admin.category.partials.name', compact('category'));
            })
            ->editColumn('sub_categories_count', function ($category) {
                $url = route('admin.catalog.category.show', $category->id);

                return '<a href="'.$url.'">'.(int) $category->sub_categories_count.'</a>';
            });

        $rawColumns = ['cover_image', 'feature_image', 'name', 'sub_categories_count', 'checkbox', 'option'];

        return $data->rawColumns($rawColumns)->make(true);
    }

    /**
     * Show the category with its subcategories and products.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        abort_unless(Auth::user()->isFromPlatform(), 403);

        $category = $this->category->find($id);
        $category->load(['featureImage', 'coverImage']);

        $subCategories = $category->subCategories()
            ->with('featureImage', 'coverImage')
            ->withCount('products')
            ->orderBy('name', 'asc')
            ->get();

        $trashes = $category->subCategories()->onlyTrashed()->get();

        $products = Product::query()
            ->whereHas('subCategories', function ($q) use ($category) {
                $q->where('sub_categories.category_id', $category->id);
            })
            ->with(['featureImage', 'image', 'subCategories'])
            ->latest()
            ->paginate(20);

        return view('admin.category.show', compact('category', 'subCategories', 'trashes', 'products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.category._create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateCategoryRequest $request)
    {
        $this->category->store($request);

        Cache::forget('all_categories');
        Cache::forget('all_categories_v2');

        return back()->with('success', trans('messages.created', ['model' => $this->model_name]));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $category = $this->category->find($id);

        return view('admin.category._edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCategoryRequest $request, $id)
    {
        $this->category->update($request, $id);

        $this->treat_cache($request, $id);

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    /**
     * Trash the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function trash(Request $request, $id)
    {
        // Check for association with sub-categories
        if ($this->category->find($id)->subCategories->count()) {
            $notice = trans('messages.model_has_association', ['model' => $this->model_name, 'associate' => trans('app.subcategories')]);

            return back()->with('error', $notice)->with('global_notice', $notice);
        }

        $this->category->trash($id);

        $this->treat_cache($request, $id);

        return back()->with('success', trans('messages.trashed', ['model' => $this->model_name]));
    }

    /**
     * Restore the specified resource from soft delete.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore(Request $request, $id)
    {
        $this->category->restore($id);

        $this->treat_cache($request, $id);

        return back()->with('success', trans('messages.restored', ['model' => $this->model_name]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $this->category->destroy($id);

        $this->treat_cache($request, $id);

        return back()->with('success', trans('messages.deleted', ['model' => $this->model_name]));
    }

    /**
     * Trash the mass resources.
     *
     * @return \Illuminate\Http\Response
     */
    public function massTrash(Request $request)
    {
        $this->category->massTrash($request->ids);

        $this->treat_cache($request, $request->ids);

        if ($request->ajax()) {
            return response()->json(['success' => trans('messages.trashed', ['model' => $this->model_name])]);
        }

        return back()->with('success', trans('messages.trashed', ['model' => $this->model_name]));
    }

    /**
     * Trash the mass resources.
     *
     * @return \Illuminate\Http\Response
     */
    public function massDestroy(Request $request)
    {
        $this->category->massDestroy($request->ids);

        $this->treat_cache($request, $request->ids);

        if ($request->ajax()) {
            return response()->json(['success' => trans('messages.deleted', ['model' => $this->model_name])]);
        }

        return back()->with('success', trans('messages.deleted', ['model' => $this->model_name]));
    }

    /**
     * Empty the Trash the mass resources.
     *
     * @return \Illuminate\Http\Response
     */
    public function emptyTrash(Request $request)
    {
        $this->category->emptyTrash($request);

        if ($request->ajax()) {
            return response()->json(['success' => trans('messages.deleted', ['model' => $this->model_name])]);
        }

        return back()->with('success', trans('messages.deleted', ['model' => $this->model_name]));
    }

    /**
     * Clear the cache when needed
     */
    private function treat_cache($request, $id)
    {
        $ids = is_array($id) ? $id : [$id];

        Cache::forget('all_categories');
        Cache::forget('all_categories_v2');
    }
}
