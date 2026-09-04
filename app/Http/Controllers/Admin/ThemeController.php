<?php

namespace App\Http\Controllers\Admin;

use App\Common\Authorizable;
use App\Events\System\SystemConfigUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\ThemeInstallationRequest;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ThemeController extends Controller
{
    use Authorizable;

    public function all()
    {
        $storeFrontThemes = collect($this->storeFrontThemes());
        $t_theme = active_theme();
        $active_theme = $storeFrontThemes->firstWhere('slug', $t_theme);
        $storeFrontThemes = $storeFrontThemes->filter(function ($value) use ($t_theme) {
            return $value['slug'] != $t_theme;
        });
        $sellingThemes = collect($this->sellingThemes());

        return view('admin.theme.index', compact('storeFrontThemes', 'active_theme', 'sellingThemes'));
    }

    public function initiate(Request $request, $theme)
    {
        if (config('app.demo') == true && config('app.debug') !== true) {
            return back()->with('warning', trans('messages.demo_restriction'));
        }

        return view('admin.theme._initiate', compact('theme'));
    }

    public function activate(ThemeInstallationRequest $request, $theme, $type = 'storefront')
    {
        if (config('app.demo') == true) {
            Session::put('theme', $theme);

            return back()->with('success', trans('messages.theme_activated', ['theme' => $theme]));
        }

        $system = SystemConfig::orderBy('id', 'asc')->first();
        $this->authorize('update', $system);

        Log::info('Installing theme '.$theme);

        try {
            if ($type == 'selling') {
                $this->sellingThemes($theme);
                $system->selling_theme = $theme;
            } else {
                $this->storeFrontThemes($theme);
                $system->active_theme = $theme;
            }
        } catch (\Exception $exception) {
            Log::info('Theme installation failed '.$theme);
            Log::error(get_exception_message($exception));

            return back()->with('error', $exception->getMessage());
        }

        if ($system->save()) {
            event(new SystemConfigUpdated($system));

            return back()->with('success', trans('messages.theme_activated', ['theme' => $theme]));
        }

        return back()->with('error', trans('messages.failed'));
    }

    private function storeFrontThemes($slug = null)
    {
        $storeFrontThemes = [];

        foreach (glob(theme_path('*'), GLOB_ONLYDIR) as $themeFolder) {
            $themeFolder = realpath($themeFolder);
            $jsonFilename = $themeFolder.'/theme.json';

            if (! file_exists($jsonFilename)) {
                continue;
            }

            $data = [];
            $json = file_get_contents($jsonFilename);

            if ($json !== '') {
                $data = json_decode($json, true);

                if ($data === null) {
                    throw new \Exception("Invalid theme.json file at [{$themeFolder}]");
                }

                if (! $data['released'] && App::environment(['production'])) {
                    continue;
                }
            }

            if ($slug && $data['slug'] == $slug) {
                $data['path'] = $themeFolder;

                return $data;
            }

            $data['assets-path'] = theme_assets_path($data['slug']);
            $data['views-path'] = theme_views_path($data['slug']);
            $storeFrontThemes[] = $data;
        }

        usort($storeFrontThemes, function ($x, $y) {
            return strnatcmp($x['name'], $y['name']);
        });

        return $storeFrontThemes;
    }

    private function sellingThemes($slug = null)
    {
        $sellingThemes = [];

        foreach (glob(selling_theme_path('*'), GLOB_ONLYDIR) as $themeFolder) {
            $themeFolder = realpath($themeFolder);
            $jsonFilename = $themeFolder.'/theme.json';

            if (! file_exists($jsonFilename)) {
                continue;
            }

            $data = [];
            $json = file_get_contents($jsonFilename);

            if ($json !== '') {
                $data = json_decode($json, true);

                if ($data === null) {
                    throw new \Exception("Invalid theme.json file at [{$themeFolder}]");
                }
            }

            if ($slug && $data['slug'] == $slug) {
                $data['path'] = $themeFolder;

                return $data;
            }

            $data['assets-path'] = selling_theme_assets_path($data['slug']);
            $data['views-path'] = selling_theme_views_path($data['slug']);
            $sellingThemes[] = $data;
        }

        usort($sellingThemes, function ($x, $y) {
            return strnatcmp($x['name'], $y['name']);
        });

        return $sellingThemes;
    }
}
