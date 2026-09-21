<?php

namespace App\Http\Controllers\Admin;

use App\Common\Authorizable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateSubCategoryRequest;
use App\Http\Requests\Validations\UpdateSubCategoryRequest;
use App\Models\Category;
use App\Repositories\SubCategory\SubCategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * SubCategories are fully platform/admin-managed, same as Category — only
 * platform admins may create/edit/delete one.
 */
class SubCategoryController extends Controller
{
    use Authorizable;

    private $model_name;

    private $subCategory;

    /**
     * construct
     */
    public function __construct(SubCategoryRepository $subCategory)
    {
        parent::__construct();
        $this->model_name = trans('app.model.subcategory');
        $this->subCategory = $subCategory;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        abort_unless(Auth::user()->isFromPlatform(), 403);

        return redirect()->route('admin.catalog.category.index');
    }

    /**
     * Show the subcategory with its products.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        abort_unless(Auth::user()->isFromPlatform(), 403);

        $subCategory = $this->subCategory->find($id);
        $subCategory->load(['category', 'featureImage', 'coverImage']);

        $products = $subCategory->products()
            ->with(['featureImage', 'image', 'subCategories'])
            ->latest()
            ->paginate(20);

        return view('admin.category.subcategory_show', compact('subCategory', 'products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $preselectedCategoryId = $request->integer('category_id') ?: null;

        if (! $preselectedCategoryId) {
            return redirect()->route('admin.catalog.category.index');
        }

        $parentCategory = Category::findOrFail($preselectedCategoryId);

        return view('admin.category._createSubCat', compact('preselectedCategoryId', 'parentCategory'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateSubCategoryRequest $request)
    {
        $subCategory = $this->subCategory->store($request);

        DB::transaction(function () use ($subCategory, $request) {
            $subCategory->attrsList()->sync($request->attrsList);
        });

        return $this->redirectToParentCategory($subCategory->category_id)
            ->with('success', trans('messages.created', ['model' => $this->model_name]));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $subCategory = $this->subCategory->find($id);
        $preselectedCategoryId = (int) $subCategory->category_id;
        $parentCategory = $subCategory->category;

        return view('admin.category._editSubCat', compact('subCategory', 'preselectedCategoryId', 'parentCategory'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSubCategoryRequest $request, $id)
    {
        $subCategory = $this->subCategory->update($request, $id);

        DB::transaction(function () use ($subCategory, $request) {
            $subCategory->attrsList()->sync($request->attrsList);
        });

        return $this->redirectToParentCategory($subCategory->category_id)
            ->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    /**
     * Trash the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function trash(Request $request, $id)
    {
        $subCategory = $this->subCategory->find($id);

        // Check for association with products
        if ($subCategory->products->count()) {
            $notice = trans('messages.model_has_association', ['model' => $this->model_name, 'associate' => trans('app.products')]);

            return $this->redirectToParentCategory($subCategory->category_id)
                ->with('error', $notice)->with('global_notice', $notice);
        }

        $categoryId = $subCategory->category_id;
        $this->subCategory->trash($id);

        return $this->redirectToParentCategory($categoryId)
            ->with('success', trans('messages.trashed', ['model' => $this->model_name]));
    }

    /**
     * Restore the specified resource from soft delete.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore(Request $request, $id)
    {
        $subCategory = $this->subCategory->findTrash($id);
        $categoryId = $subCategory->category_id;

        $this->subCategory->restore($id);

        return $this->redirectToParentCategory($categoryId)
            ->with('success', trans('messages.restored', ['model' => $this->model_name]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $subCategory = $this->subCategory->findTrash($id);
        $categoryId = $subCategory->category_id;

        $this->subCategory->destroy($id);

        return $this->redirectToParentCategory($categoryId)
            ->with('success', trans('messages.deleted', ['model' => $this->model_name]));
    }

    /**
     * Trash the mass resources.
     *
     * @return \Illuminate\Http\Response
     */
    public function massTrash(Request $request)
    {
        $this->subCategory->massTrash($request->ids);

        if ($request->ajax()) {
            return response()->json(['success' => trans('messages.trashed', ['model' => $this->model_name])]);
        }

        return redirect()->route('admin.catalog.category.index')
            ->with('success', trans('messages.trashed', ['model' => $this->model_name]));
    }

    /**
     * Trash the mass resources.
     *
     * @return \Illuminate\Http\Response
     */
    public function massDestroy(Request $request)
    {
        $this->subCategory->massDestroy($request->ids);

        if ($request->ajax()) {
            return response()->json(['success' => trans('messages.deleted', ['model' => $this->model_name])]);
        }

        return redirect()->route('admin.catalog.category.index')
            ->with('success', trans('messages.deleted', ['model' => $this->model_name]));
    }

    /**
     * Empty the Trash the mass resources.
     *
     * @return \Illuminate\Http\Response
     */
    public function emptyTrash(Request $request)
    {
        $this->subCategory->emptyTrash($request);

        if ($request->ajax()) {
            return response()->json(['success' => trans('messages.deleted', ['model' => $this->model_name])]);
        }

        return redirect()->route('admin.catalog.category.index')
            ->with('success', trans('messages.deleted', ['model' => $this->model_name]));
    }

    /**
     * Send the admin back to the parent category page (where subcategories are managed).
     *
     * @param  int|null  $categoryId
     * @return \Illuminate\Http\RedirectResponse
     */
    private function redirectToParentCategory($categoryId)
    {
        if ($categoryId) {
            return redirect()->route('admin.catalog.category.show', $categoryId);
        }

        return redirect()->route('admin.catalog.category.index');
    }
}
