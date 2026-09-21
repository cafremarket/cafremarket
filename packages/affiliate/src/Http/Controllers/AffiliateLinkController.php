<?php

namespace Incevio\Package\Affiliate\Http\Controllers;

use App\Models\Inventory;
use App\Models\Shop;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Incevio\Package\Affiliate\Models\AffiliateLink;

class AffiliateLinkController extends Controller
{
    /**
     * Display a listing of the affiliate links.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $links = AffiliateLink::with([
            'inventory.shop',
            'inventory.attributeValues.attribute',
            'inventory.product',
        ])->mine()->get();
        $shops = Shop::active()
            ->with(['primaryAddress', 'addresses', 'config'])
            ->orderBy('name')
            ->get()
            ->map(function (Shop $shop) {
                $address = $shop->storeAddress();

                return [
                    'id' => $shop->id,
                    'name' => $shop->name,
                    'email' => $shop->email
                        ?: optional($shop->config)->support_email
                        ?: optional($shop->config)->default_sender_email_address,
                    'address' => $address ? $address->toString() : null,
                ];
            })
            ->values();

        return view('affiliate::backend.index', compact('links', 'shops'));
    }

    public function products(Request $request)
    {
        $shopId = (int) $request->input('shop_id');

        if ($shopId < 1) {
            return response()->json([]);
        }

        $inventories = Inventory::query()
            ->where('shop_id', $shopId)
            ->active()
            ->with(['attributeValues.attribute', 'product', 'image'])
            ->orderBy('title')
            ->orderBy('sku')
            ->get()
            ->filter(function ($inventory) {
                return $inventory->hasAffiliateCommission();
            })
            ->values();

        $countsByProduct = $inventories->groupBy('product_id')->map->count();

        $products = $inventories->map(function ($inventory) use ($countsByProduct) {
            $productName = optional($inventory->product)->name ?: $inventory->title;
            $variantAttrs = $inventory->attributeValues
                ->map(function ($value) {
                    $attributeName = optional($value->attribute)->name;

                    return $attributeName
                        ? trim($attributeName).': '.trim((string) $value->value)
                        : trim((string) $value->value);
                })
                ->filter()
                ->implode(' · ');

            $hasSiblings = (int) ($countsByProduct[$inventory->product_id] ?? 0) > 1;
            $isVariant = $hasSiblings || $variantAttrs !== '' || ! is_null($inventory->parent_id);

            if ($isVariant && $variantAttrs !== '') {
                $title = $productName.' — '.$variantAttrs;
            } elseif ($isVariant) {
                $title = $productName.' ('.$inventory->sku.')';
            } else {
                $title = $inventory->title ?: $productName;
            }

            return [
                'id' => $inventory->id,
                'title' => $title,
                'group' => $productName,
                'variant' => $variantAttrs !== '' ? $variantAttrs : null,
                'sku' => $inventory->sku,
                'is_variant' => $isVariant,
                'price' => get_formated_currency($inventory->sale_price, 2),
                'commission' => $inventory->affiliate_commission_percentage_text,
            ];
        })->values();

        return response()->json($products);
    }

    public function storeFromPicker(Request $request)
    {
        $data = $request->validate([
            'shop_id' => 'required|integer|exists:shops,id',
            'inventory_id' => 'required|integer|exists:inventories,id',
        ], [
            'shop_id.required' => trans('packages.affiliate.select_store'),
            'inventory_id.required' => trans('packages.affiliate.select_product'),
        ]);

        $inventory = Inventory::where('id', $data['inventory_id'])
            ->where('shop_id', $data['shop_id'])
            ->first();

        if (! $inventory) {
            return redirect()->route('affiliate.link.index')
                ->with('error', trans('packages.affiliate.product_not_in_store'));
        }

        if (! $inventory->hasAffiliateCommission()) {
            return redirect()->route('affiliate.link.index')
                ->with('error', trans('packages.affiliate.product_not_eligible'));
        }

        return $this->store($request, $inventory);
    }

    /**
     * Display the commissions for a specific affiliate link.
     *
     * @param AffiliateLink $link The affiliate link for which to display commissions.
     * @return \Illuminate\View\View The view displaying the commissions.
     */
    public function showLinkCommissions(AffiliateLink $link)
    {
        abort_unless((int) $link->affiliate_id === (int) Auth::guard('affiliate')->id(), 403);

        $commissions = $link->commissions()->get();

        return view('affiliate::backend.showLinkCommission', compact('commissions'));
    }

    /**
     * Create a new affiliate link.
     *
     * @param Inventory $inventory
     * @return \Illuminate\View\View
     */
    public function create(Inventory $inventory)
    {
        return view('affiliate::backend.create', compact('inventory'));
    }

    /**
     * Store a newly created affiliate link in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param Inventory $inventory
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Inventory $inventory)
    {
        $affiliate = Auth::guard('affiliate')->user();

        if (! $inventory->hasAffiliateCommission()) {
            return redirect()->route('affiliate.link.index')
                ->with('error', trans('packages.affiliate.product_not_eligible'));
        }

        $existing = $affiliate->affiliateLinks()
            ->where('inventory_id', $inventory->id)
            ->first();

        if ($existing) {
            return redirect()->route('affiliate.link.index')
                ->with('success', trans('packages.affiliate.link_already_exists'));
        }

        $affiliate->affiliateLinks()->create([
            'inventory_id' => $inventory->id,
            'slug' => AffiliateLink::generateUniqueSlug(),
        ]);

        return redirect()->route('affiliate.link.index')
            ->with('success', trans('packages.affiliate.link_created_successfully'));
    }

    /**
     * Display the form for editing the specified affiliate link.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param int $id The ID of the affiliate link to edit.
     * @return \Illuminate\View\View The view for editing the affiliate link.
     */
    public function edit(AffiliateLink $link)
    {
        abort_unless((int) $link->affiliate_id === (int) Auth::guard('affiliate')->id(), 403);

        return view('affiliate::backend.edit', compact('link'));
    }

    /**
     * Update the specified affiliate link in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param AffiliateLink $link
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, AffiliateLink $link)
    {
        abort_unless((int) $link->affiliate_id === (int) Auth::guard('affiliate')->id(), 403);

        $request->validate([
            'slug' => 'required|alpha_dash|unique:affiliate_links,slug,'.$link->id,
        ]);

        $link->update([
            'slug' => $request->slug,
        ]);

        return redirect()->back()
            ->with('success', trans('packages.affiliate.link_updated_successfully'));
    }

    /**
     * Delete an affiliate link.
     *
     * @param AffiliateLink $link
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(AffiliateLink $link)
    {
        abort_unless((int) $link->affiliate_id === (int) Auth::guard('affiliate')->id(), 403);

        $link->delete();

        return redirect()->route('affiliate.link.index')
            ->with('success', trans('packages.affiliate.link_deleted_successfully'));
    }
}
