<?php

namespace App\Http\Controllers\Admin;

use App\Common\Authorizable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateWebBannerRequest;
use App\Http\Requests\Validations\UpdateWebBannerRequest;
use App\Models\Banner;
use App\Models\BannerGroup;
use App\Repositories\Banner\BannerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WebBannerController extends Controller
{
    use Authorizable;

    private string $model;

    public function __construct(private BannerRepository $banner)
    {
        parent::__construct();

        $this->model = trans('app.model.web_banner');
    }

    /**
     * Platform homepage banners (shop_id is null).
     */
    public function index()
    {
        $banners = Banner::with('group', 'featureImage', 'images')
            ->whereNull('shop_id')
            ->orderBy('group_id')
            ->orderBy('order')
            ->get();

        $groups = BannerGroup::orderBy('id')->get();

        $homepageGroupIds = ['group_1', 'group_2', 'group_3'];

        return view('admin.web_banner.index', compact('banners', 'groups', 'homepageGroupIds'));
    }

    public function create(Request $request)
    {
        $defaultGroup = $request->query('group_id');

        return view('admin.web_banner._create', compact('defaultGroup'));
    }

    public function store(CreateWebBannerRequest $request)
    {
        $this->banner->store($request);

        Cache::forget('banners');

        return back()->with('success', trans('messages.created', ['model' => $this->model]));
    }

    public function edit(Banner $banner)
    {
        $this->ensurePlatformBanner($banner);

        return view('admin.web_banner._edit', compact('banner'));
    }

    public function update(UpdateWebBannerRequest $request, Banner $banner)
    {
        $this->ensurePlatformBanner($banner);

        $this->banner->update($request, $banner);

        Cache::forget('banners');

        return back()->with('success', trans('messages.updated', ['model' => $this->model]));
    }

    public function destroy(Banner $banner)
    {
        $this->ensurePlatformBanner($banner);

        $banner->flushImages();
        $banner->forceDelete();

        Cache::forget('banners');

        return back()->with('success', trans('messages.deleted', ['model' => $this->model]));
    }

    public function massDestroy(Request $request)
    {
        $ids = collect($request->ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->all();

        $banners = Banner::whereNull('shop_id')->whereIn('id', $ids)->get();

        foreach ($banners as $banner) {
            $banner->flushImages();
            $banner->forceDelete();
        }

        Cache::forget('banners');

        if ($request->ajax()) {
            return response()->json(['success' => trans('messages.deleted', ['model' => $this->model])]);
        }

        return back()->with('success', trans('messages.deleted', ['model' => $this->model]));
    }

    private function ensurePlatformBanner(Banner $banner): void
    {
        abort_unless($banner->shop_id === null, 404);
    }
}
