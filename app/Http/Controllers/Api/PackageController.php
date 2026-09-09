<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function isLoaded(Request $request, $slug)
    {
        // Multi-store "checkout all" is a built-in marketplace feature (one order per shop).
        if ($slug === 'checkout') {
            return response()->json(['data' => true]);
        }

        return response()->json([
            'data' => (bool) is_incevio_package_loaded($slug),
        ]);
    }
}
