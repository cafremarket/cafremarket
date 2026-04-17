<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\GenerateAppKeyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ApplicationKeyController extends Controller
{
    /**
     * Show confirmation form
     *
     * @return void
     */
    public function confirm(Request $request)
    {
        return view('admin.system.generate_app_key');
    }

    /**
     * generate app keys
     *
     * @param  Request  $request
     * @return void
     */
    public function generate(GenerateAppKeyRequest $request)
    {
        if (config('app.demo') == true) {
            return back()->with('warning', trans('messages.demo_restriction'));
        }

        Artisan::call('incevio:generate-key');
        Artisan::call('config:clear');

        return back()->with('success', trans('app.application_key_regenerated'));
    }
}
