<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    /**
     * Change Language
     *
     * @param  string  $locale
     * @return \Illuminate\Http\Response
     */
    public function change($locale = 'en')
    {
        Session::put('locale', $locale);

        Cache::forget('all_categories');

        return redirect()->back();
    }
}
