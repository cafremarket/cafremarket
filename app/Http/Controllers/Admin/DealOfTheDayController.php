<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromotionAccessRequest;
use App\Models\DealOfTheDay;
use App\Models\Inventory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DealOfTheDayController extends Controller
{
    /**
     * Calendar planner for Deal of the Day.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(PromotionAccessRequest $request)
    {
        $month = $request->get('month', now()->format('Y-m'));

        try {
            $cursor = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } catch (\Exception $e) {
            $cursor = now()->startOfMonth();
            $month = $cursor->format('Y-m');
        }

        $start = $cursor->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
        $end = $cursor->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $deals = DealOfTheDay::with(['inventory.shop:id,name', 'inventory.product:id,name'])
            ->whereBetween('deal_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('id')
            ->get()
            ->groupBy(function ($deal) {
                return $deal->deal_date->format('Y-m-d');
            });

        $upcoming = DealOfTheDay::with(['inventory.shop:id,name', 'inventory.product:id,name'])
            ->where('deal_date', '>=', now()->toDateString())
            ->orderBy('deal_date')
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->groupBy(function ($deal) {
                return $deal->deal_date->format('Y-m-d');
            });

        $prevMonth = $cursor->copy()->subMonth()->format('Y-m');
        $nextMonth = $cursor->copy()->addMonth()->format('Y-m');

        return view('admin.deal_of_the_day.calendar', compact(
            'cursor',
            'month',
            'start',
            'end',
            'deals',
            'upcoming',
            'prevMonth',
            'nextMonth'
        ));
    }

    /**
     * Assign / replace products for a calendar day (multiple allowed).
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function assign(PromotionAccessRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'deal_date' => 'required|date_format:Y-m-d',
            'inventory_ids' => 'required|array|min:1',
            'inventory_ids.*' => 'integer|exists:inventories,id',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        $ids = array_values(array_unique(array_map('intval', $request->inventory_ids)));

        $validIds = Inventory::query()
            ->whereIn('id', $ids)
            ->where('active', 1)
            ->pluck('id')
            ->all();

        if (empty($validIds)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'No valid active products selected.'], 422);
            }

            return back()->with('error', 'No valid active products selected.');
        }

        // Preserve admin selection order
        $orderedIds = array_values(array_filter($ids, function ($id) use ($validIds) {
            return in_array($id, $validIds, true);
        }));

        DB::transaction(function () use ($request, $orderedIds) {
            DealOfTheDay::where('deal_date', $request->deal_date)->delete();

            foreach ($orderedIds as $inventoryId) {
                DealOfTheDay::create([
                    'deal_date' => $request->deal_date,
                    'inventory_id' => $inventoryId,
                ]);
            }
        });

        $this->forgetDealCaches($request->deal_date);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'deal' => [
                    'date' => $request->deal_date,
                    'inventory_ids' => $orderedIds,
                    'count' => count($orderedIds),
                ],
            ]);
        }

        return back()->with('success', trans('messages.updated', ['model' => trans('app.deal_of_the_day')]));
    }

    /**
     * Clear all deals for a calendar day.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function clear(PromotionAccessRequest $request)
    {
        $validator = Validator::make($request->all(), [
            'deal_date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            return back()->withErrors($validator);
        }

        DealOfTheDay::where('deal_date', $request->deal_date)->delete();
        $this->forgetDealCaches($request->deal_date);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', trans('messages.deleted', ['model' => trans('app.deal_of_the_day')]));
    }

    private function forgetDealCaches(string $date): void
    {
        Cache::forget('deal_of_the_day_'.$date);
        Cache::forget('deal_of_the_day_items_'.$date);
        Cache::forget('deal_of_the_day_'.now()->toDateString());
        Cache::forget('deal_of_the_day_items_'.now()->toDateString());
    }
}
