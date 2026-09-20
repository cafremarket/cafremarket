<?php

namespace App\Http\Controllers\Admin;

use App\Common\Authorizable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateSubCategoryRequest;
use App\Http\Requests\Validations\UpdateSubCategoryRequest;
use App\Repositories\SubCategory\SubCategoryRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        $this->model_name = trans('app.model.category');
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

        $subCategories = $this->subCategory->all();

        $trashes = $this->subCategory->trashOnly();

        return view('admin.category.subcategory', compact('subCategories', 'trashes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.category._createSubCat');
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
        $subCategory = $this->subCategory->find($id);

        return view('admin.category._editSubCat', compact('subCategory'));
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
        // Check for association with products
        if ($this->subCategory->find($id)->products->count()) {
            $notice = trans('messages.model_has_association', ['model' => $this->model_name, 'associate' => trans('app.products')]);

            return back()->with('error', $notice)->with('global_notice', $notice);
        }

        $this->subCategory->trash($id);

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
        $this->subCategory->restore($id);

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
        $this->subCategory->destroy($id);

        return back()->with('success', trans('messages.deleted', ['model' => $this->model_name]));
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

        return back()->with('success', trans('messages.trashed', ['model' => $this->model_name]));
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

        return back()->with('success', trans('messages.deleted', ['model' => $this->model_name]));
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

        return back()->with('success', trans('messages.deleted', ['model' => $this->model_name]));
    }
}
