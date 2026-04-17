<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;

class GiftCardController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return Response
     */
    public function index()
    {
        $giftCards = GiftCard::all();

        return view('theme::gift_cards', compact('giftCards'));
    }
}
