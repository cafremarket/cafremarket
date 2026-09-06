<?php

namespace App\Http\Controllers\Admin;

use App\Common\Authorizable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateAppBannerRequest;
use App\Http\Requests\Validations\UpdateAppBannerRequest;
use App\Models\Banner;
use App\Models\BannerGroup;
use App\Repositories\Banner\BannerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AppBannerController extends Controller
{
    use Authorizable;

    private string $model;

    public function __construct(private BannerRepository $banner)
    {
        parent::__construct();

        $this->model = trans('app.model.app_banner');
    }

    /**
     * Platform customer-app banners (shop_id null, channel=app).
     */
    public function index()
    {
        $banners = Banner::with('group', 'featureImage', 'images')
            ->whereNull('shop_id')
            ->forApp()
            ->orderBy('group_id')
            ->orderBy('order')
            ->get();

        $groups = BannerGroup::orderBy('id')->get();
        $homepageGroupIds = ['group_1', 'group_2', 'group_3'];

        return view('admin.app_banner.index', compact('banners', 'groups', 'homepageGroupIds'));
    }

    public function create(Request $request)
    {
        $defaultGroup = $request->query('group_id');

        return view('admin.app_banner._create', compact('defaultGroup'));
    }

    public function store(CreateAppBannerRequest $request)
    {
        $this->banner->store($request);

        Cache::forget('app_banners');

        return back()->with('success', trans('messages.created', ['model' => $this->model]));
    }

    public function edit(Banner $banner)
    {
        $this->ensureAppBanner($banner);

        return view('admin.app_banner._edit', compact('banner'));
    }

    public function update(UpdateAppBannerRequest $request, Banner $banner)
    {
        $this->ensureAppBanner($banner);

        $this->banner->update($request, $banner);

        Cache::forget('app_banners');

        return back()->with('success', trans('messages.updated', ['model' => $this->model]));
    }

    public function destroy(Banner $banner)
    {
        $this->ensureAppBanner($banner);

        $banner->flushImages();
        $banner->forceDelete();

        Cache::forget('app_banners');

        return back()->with('success', trans('messages.deleted', ['model' => $this->model]));
    }

    public function massDestroy(Request $request)
    {
        $ids = collect($request->ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->all();

        $banners = Banner::whereNull('shop_id')->forApp()->whereIn('id', $ids)->get();

        foreach ($banners as $banner) {
            $banner->flushImages();
            $banner->forceDelete();
        }

        Cache::forget('app_banners');

        if ($request->ajax()) {
            return response()->json(['success' => trans('messages.deleted', ['model' => $this->model])]);
        }

        return back()->with('success', trans('messages.deleted', ['model' => $this->model]));
    }

    private function ensureAppBanner(Banner $banner): void
    {
        abort_unless($banner->shop_id === null && $banner->channel === Banner::CHANNEL_APP, 404);
    }
}
