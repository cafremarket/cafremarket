<?php

namespace Incevio\Package\DynamicPopup\Http\Controllers;

use App\Models\System;
use App\Common\Authorizable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Incevio\Package\DynamicPopup\Enums\PopupTypes;

class PopupController extends Controller
{
    //use Authorizable;

    /**
     * construct
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $popup = get_popup_data();
        $popup_types = PopupTypes::list();

        return view('DynamicPopup::index', compact('popup', 'popup_types'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'css' => 'nullable|valid_css',
            'background_image' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Update the option table
        update_or_create_option_table_record('dynamic_popup', $request->only('type', 'delay', 'css'));

        if ($request->hasFile('background_image')) {
            $system = System::orderBy('id', 'asc')->first();

            $system->updateImage($request->file('background_image'), 'popup');
        }

        Cache::forget('dynamic_popup');

        return back()->with('success', trans('DynamicPopup::lang.updated'));
    }
}
