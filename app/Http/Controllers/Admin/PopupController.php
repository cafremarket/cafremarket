<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreatePopupRequest;
use App\Http\Requests\Validations\UpdatePopupRequest;
use App\Models\Popup;
use App\Repositories\Popup\PopupRepository;
use App\Services\Cache\CatalogCache;
use Illuminate\Http\Request;

class PopupController extends Controller
{
    private string $model;

    public function __construct(private PopupRepository $popup)
    {
        parent::__construct();

        $this->model = trans('app.model.popup');
    }

    public function index()
    {
        $popups = Popup::orderBy('priority')->orderByDesc('id')->get();

        return view('admin.popup.index', compact('popups'));
    }

    public function create()
    {
        return view('admin.popup._create');
    }

    public function store(CreatePopupRequest $request)
    {
        $this->popup->store($request);

        CatalogCache::bumpCatalog();

        return redirect()->route('admin.popup.index')
            ->with('success', trans('messages.created', ['model' => $this->model]));
    }

    public function edit(Popup $popup)
    {
        return view('admin.popup._edit', compact('popup'));
    }

    public function update(UpdatePopupRequest $request, Popup $popup)
    {
        $this->popup->update($request, $popup);

        CatalogCache::bumpCatalog();

        return redirect()->route('admin.popup.index')
            ->with('success', trans('messages.updated', ['model' => $this->model]));
    }

    public function destroy(Popup $popup)
    {
        $popup->flushImages();
        $popup->forceDelete();

        CatalogCache::bumpCatalog();

        return back()->with('success', trans('messages.deleted', ['model' => $this->model]));
    }

    public function massDestroy(Request $request)
    {
        $ids = collect($request->ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->all();

        $popups = Popup::whereIn('id', $ids)->get();

        foreach ($popups as $popup) {
            $popup->flushImages();
            $popup->forceDelete();
        }

        CatalogCache::bumpCatalog();

        if ($request->ajax()) {
            return response()->json(['success' => trans('messages.deleted', ['model' => $this->model])]);
        }

        return back()->with('success', trans('messages.deleted', ['model' => $this->model]));
    }
}
