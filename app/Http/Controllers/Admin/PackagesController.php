<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminOnlyAccessRequest;
use App\Http\Requests\Validations\PackageInstallationRequest;
use App\Models\Package;
use App\Services\PackageInstaller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackagesController extends Controller
{
    public function index(AdminOnlyAccessRequest $request)
    {
        $installables = $this->scanPackages();
        $installedPackages = Package::all();

        return view('admin.packages.index', compact('installables', 'installedPackages'));
    }

    public function upload(AdminOnlyAccessRequest $request)
    {
        return view('admin.packages._upload');
    }

    public function save(AdminOnlyAccessRequest $request)
    {
        echo '<pre>';
        print_r($request->all());
        echo '<pre>';
        exit('end!');
    }

    public function initiate(AdminOnlyAccessRequest $request, string $package)
    {
        if ($this->isDemo()) {
            return back()->with('warning', trans('messages.demo_restriction'));
        }

        $installable = $this->scanPackages($package);

        if ($installable && Package::where('slug', $installable['slug'])->exists()) {
            return back()->with('error', trans('messages.package_installed_already', ['package' => $package]));
        }

        return view('admin.packages._initiate', compact('installable'));
    }

    public function upgrade(AdminOnlyAccessRequest $request, string $package)
    {
        if ($this->isDemo()) {
            return back()->with('warning', trans('messages.demo_restriction'));
        }

        $installable = $this->scanPackages($package);

        if (! $installable) {
            return back()->with('error', trans('messages.package_not_found', ['package' => $package]));
        }

        try {
            $installer = new PackageInstaller($request, $installable);
            $installer->upgrade();
        } catch (\Exception $e) {
            Log::error($e);

            return redirect()->route('admin.packages')->with('error', $e->getMessage());
        }

        return redirect()->route('admin.packages')->with('success', trans('messages.package_upgraded_success', ['package' => $package]));
    }

    public function install(PackageInstallationRequest $request, string $package)
    {
        if ($this->isDemo()) {
            return back()->with('warning', trans('messages.demo_restriction'));
        }

        $installable = $this->scanPackages($package);

        if (! $installable) {
            return back()->with('error', trans('messages.package_not_found', ['package' => $package]));
        }

        $installer = null;

        try {
            $installer = new PackageInstaller($request, $installable);
            $installer->install();
        } catch (\Exception $e) {
            Log::error($e);

            if ($installer) {
                $installer->uninstall();
            }

            Package::where('slug', $package)->delete();

            return back()->with('error', $e->getMessage());
        }

        Artisan::call('cache:clear');
        Artisan::call('route:clear');

        return back()->with('success', trans('messages.package_installed_success', ['package' => $package]));
    }

    public function uninstall(AdminOnlyAccessRequest $request, string $package)
    {
        if ($this->isDemo()) {
            return back()->with('warning', trans('messages.demo_restriction'));
        }

        $registered = Package::where('slug', $package)->firstOrFail();
        $uninstallable = $this->scanPackages($package);

        try {
            $installer = new PackageInstaller($request, $uninstallable);

            if ($installer->uninstall() && $registered->delete()) {
                Artisan::call('cache:clear');
                Artisan::call('route:clear');

                return back()->with('success', trans('messages.package_uninstalled_success', ['package' => $package]));
            }
        } catch (\Exception $e) {
            Log::error($e);

            return back()->with('error', $e->getMessage());
        }

        return back()->with('error', trans('messages.failed'));
    }

    public function activation(AdminOnlyAccessRequest $request, string $slug)
    {
        if ($this->isDemo()) {
            return response('error', 444);
        }

        $package = Package::where('slug', $slug)->first();

        if ($package) {
            $package->active = ! $package->active;
            $package->save();
            Artisan::call('cache:clear');

            return response('success', 200);
        }

        if ($unregistered = $this->scanPackages($slug)) {
            Package::create($unregistered);
        }

        return response('success', 200);
    }

    public function updateConfig(Request $request)
    {
        if (updateOptionTable($request)) {
            return back()->with('success', trans('messages.package_settings_updated'));
        }

        return back()->with('error', trans('messages.failed'));
    }

    public function toggleConfig(Request $request, string $option)
    {
        if ($this->isDemo()) {
            return response('error', 444);
        }

        $updated = DB::table('options')->where('option_name', $option)->update([
            'option_value' => DB::raw('NOT option_value'),
        ]);

        if ($updated) {
            Cache::forget($option);

            return response('success', 200);
        }

        return response('error', 405);
    }

    private function scanPackages(?string $slug = null): array
    {
        $packages = [];

        foreach (glob(base_path('packages/*'), GLOB_ONLYDIR) as $dir) {
            $manifest = $dir.'/manifest.json';

            if (! file_exists($manifest)) {
                continue;
            }

            $json = file_get_contents($manifest);

            if ($json === '') {
                continue;
            }

            $data = json_decode($json, true);

            if ($data === null) {
                throw new \Exception("Invalid manifest.json file at [{$dir}]");
            }

            $data['path'] = $dir;

            if ($slug && $data['slug'] == $slug) {
                return $data;
            }

            $packages[] = $data;
        }

        usort($packages, function ($x, $y) {
            return strcasecmp($x['name'], $y['name']);
        });

        return $packages;
    }

    private function isDemo(): bool
    {
        return config('app.demo') && ! config('app.debug');
    }
}
