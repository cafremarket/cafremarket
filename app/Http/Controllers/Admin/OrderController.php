<?php

namespace App\Http\Controllers\Admin;

use App\Common\Authorizable;
use App\Events\Order\OrderCreated;
use App\Events\Order\OrderFulfilled;
use App\Helpers\ListHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateOrderRequest;
use App\Http\Requests\Validations\FulfillOrderRequest;
use App\Models\DeliveryBoy;
use App\Models\Order;
use App\Repositories\Order\OrderRepository;
use App\Services\Delivery\DeliveryDispatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Yajra\Datatables\Datatables;
use ZipArchive;

class OrderController extends Controller
{
    use Authorizable;

    private $model_name;

    private $order;

    /**
     * construct
     */
    public function __construct(OrderRepository $order)
    {
        parent::__construct();
        $this->model_name = trans('app.model.order');
        $this->order = $order;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.order.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function searchCustomer()
    {
        return view('admin.order._search_customer');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        $data['customer'] = $this->order->getCustomer($request->input('customer_id'));

        $data['cart_lists'] = $this->order->getCartList($request->input('customer_id'));

        if ($request->has('cart_id')) {
            $data['cart'] = $this->order->getCart($request->input('cart_id'));
        }

        return view('admin.order.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(CreateOrderRequest $request)
    {
        if (is_null($request->input('cart'))) {
            return back()->with('warning', trans('theme.notify.cart_empty'));
        }

        $order = $this->order->store($request);

        event(new OrderCreated($order));

        return redirect()->route('admin.order.order.index')
            ->with('success', trans('messages.created', ['model' => $this->model_name]));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $order = $this->order->find($id);

        $order->load('inventories.images', 'activities.causer');

        $this->authorize('view', $order); // Check permission

        // Platform admins get a read-only overview — no action buttons of any
        // kind. Assigning delivery boys/couriers, fulfilling, cancelling,
        // refunding, etc. are the merchant's own responsibility on their panel.
        if (Auth::user()->isFromPlatform()) {
            return view('admin.order._show_overview', compact('order'));
        }

        $address = $order->customer->primaryAddress();

        if (is_incevio_package_loaded('affiliate')) {
            $commissions = $order->affiliateCommissions()->get();

            return view('admin.order.show', compact('order', 'address', 'commissions'));
        }

        return view('admin.order.show', compact('order', 'address'));
    }

    /**
     * Display a page to process bulk order processing.
     *
     * @param Request
     * @param int For filtering by payment status
     * @param int For filtering by order status
     * @return \Illuminate\Http\JsonResponse
     */
    public function showBulkProcess(Request $request, $paymentStatus = 0, $orderStatus = 0, $fulfilmentStatus = 0)
    {
        $orders = Order::query();

        if (Auth::user()->isFromMerchant()) {
            $orders->where('shop_id', Auth::user()->merchantId()); // Merchants must only see their own orders
            $orders->visibleToVendor(); // Bank transfer orders stay hidden until admin verifies and marks paid
        }

        if ($fulfilmentStatus != 0) {
            $orders->where('fulfilment_type', $fulfilmentStatus);
        }

        if ($paymentStatus == Order::PAYMENT_STATUS_PAID) {
            $orders->paid();
        } elseif ($paymentStatus == Order::PAYMENT_STATUS_UNPAID) {
            $orders->unpaid();
        }

        if ($orderStatus != 0) {
            // Soft-deleted (archived) canceled orders must still appear when
            // filtering the store/admin panel by canceled status.
            if ((int) $orderStatus === Order::STATUS_CANCELED) {
                $orders->withTrashed()->where('order_status_id', Order::STATUS_CANCELED);
            } else {
                $orders->where('order_status_id', $orderStatus);
            }
        }

        $orders = $orders->with(['cancellation', 'paymentMethod', 'shop', 'customer'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Datatables::of($orders)
            ->editColumn('checkbox', function ($order) {
                return view('admin.partials.actions.order.checkbox', compact('order'));
            })
            ->addColumn('order', function ($order) {
                return view('admin.partials.actions.order.order', compact('order'));
            })
            ->addColumn('order_date', function ($order) {
                return view('admin.partials.actions.order.order_date', compact('order'));
            })
            ->editColumn('shop', function ($order) {
                return view('admin.partials.actions.order.shop', compact('order'));
            })
            ->editColumn('customer_name', function ($order) {
                return view('admin.partials.actions.order.customer_name', compact('order'));
            })
            ->editColumn('grand_total', function ($order) {
                return view('admin.partials.actions.order.grand_total', compact('order'));
            })
            ->editColumn('payment_status', function ($order) {
                return view('admin.partials.actions.order.payment_status', compact('order'));
            })
            ->editColumn('order_status', function ($order) {
                $order_statuses = ListHelper::order_statuses();

                return view('admin.partials.actions.order.order_status', compact('order', 'order_statuses'));
            })
            ->editColumn('option', function ($order) {
                return view('admin.partials.actions.order.option', compact('order'));
            })
            ->rawColumns(['checkbox', 'order', 'order_date', 'shop', 'customer_name', 'grand_total', 'payment_status', 'order_status', 'option'])
            ->make(true);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function invoice($id)
    {
        $order = $this->order->find($id);

        $this->authorize('view', $order); // Check permission

        return $order->invoice('download'); // Download the invoice
    }

    /**
     * Download the shipping label for the order
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function shippingLabel($id)
    {
        $order = $this->order->find($id);

        $this->authorize('view', $order); // Check permission

        return $order->shippingLabelPdf('download');
    }

    /**
     * Download invoices of all selected orders
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function downloadSelected(Request $request)
    {
        $filePaths = [];
        $folder_name = $this->getUniqueFolderNameForInvoice();
        $platform_title = get_platform_title();

        foreach ($request->ids as $id) {
            $order = Order::find($id);
            $this->authorize('view', $order); // Check permission

            $file_name = "{$platform_title}_{$order->order_number}.pdf";
            $file_path = public_path("invoice_tmp/{$folder_name}/{$file_name}");
            $folder_path = public_path("invoice_tmp/{$folder_name}");

            if (! file_exists($folder_path)) {
                mkdir($folder_path, 0777, true);
            }

            $order->invoice('save', $file_path); // Generate PDF

            // Store generated file paths for zipping and deletion
            array_push($filePaths, $file_path);
        }

        // Create ZIP archive
        $zip = new ZipArchive;
        $zipFileName = 'Invoices.zip';
        $zipFilePath = public_path("invoice_tmp/$folder_name/$zipFileName");

        // If a file at zipFilePath exists delete the existing file
        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }

        if ($zip->open($zipFilePath, ZipArchive::CREATE)) {
            foreach ($filePaths as $filePath) {
                $relativeName = basename($filePath);
                $zip->addFile($filePath, $relativeName);
            }
        }

        $zip->close();

        // Delete the files used to create the zip file
        foreach ($filePaths as $filePath) {
            \File::delete($filePath);
        }

        $zipFilePath = URL::to("/invoice_tmp/$folder_name/$zipFileName");

        $response = [
            'download' => trans('messages.created', ['model' => $this->model_name]),
            'download_url' => URL::to($zipFilePath),
            'download_file_name' => 'Invoices.zip',
        ];

        // Prepare response data
        if ($request->ajax()) {
            return response()->json($response);
        }

        return response()->json(['error' => trans('messages.failed')]);
    }

    /**
     * Show the fulfillment form for the specified order.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function fulfillment($id)
    {
        $order = $this->order->find($id);

        $this->authorize('fulfill', $order); // Check permission

        $carriers = ListHelper::carriers($order->shop_id);

        return view('admin.order._fulfill', compact('order', 'carriers'));
    }

    /**
     * Get list of delivery boys
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function deliveryBoys($id, DeliveryDispatchService $dispatchService)
    {
        $order = $this->order->find($id);

        $shopRiders = $dispatchService->getAvailableShopRiders($order->shop_id);
        $deliveryboys = $shopRiders->mapWithKeys(function ($rider) {
            $label = "#{$rider->id} — {$rider->getName()} ({$rider->email})";

            return [$rider->id => $label];
        });
        $shopRidersAvailable = $shopRiders->count();

        return view('admin.order._assign_fulfillment', compact('deliveryboys', 'order', 'shopRidersAvailable'));
    }

    /**
     * Assign a delivery boy to an order
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assignDeliveryBoy(Request $request, $id, DeliveryDispatchService $dispatchService)
    {
        $order = $this->order->find($id);

        if ($order->isDelivered()) {
            return back()->with('error', trans('app.order_already_delivered'));
        }

        if ($request->filled('delivery_boy_id')) {
            $rider = DeliveryBoy::findOrFail($request->delivery_boy_id);
            $dispatchService->assignShopRider($order, $rider);
        }

        return back()->with('success', trans('messages.created', ['model' => $this->model_name]));
    }

    /**
     * Show the courier details form for an order
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function courierForm($id, DeliveryDispatchService $dispatchService)
    {
        return $this->deliveryBoys($id, $dispatchService);
    }

    /**
     * Add/update courier details for an order
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assignCourier(Request $request, $id)
    {
        if (! $request->filled('courier_name') || ! $request->filled('courier_phone')) {
            return back()->with('error', trans('app.courier_details_required'));
        }

        try {
            \DB::transaction(function () use ($request, $id) {
                // Locked re-read: an in-flight "assign" that started before a
                // concurrent delivery confirmation must not be allowed to save
                // over it — see DeliveryDispatchService::assignShopRider for
                // the full race explanation.
                $order = Order::whereKey($id)->lockForUpdate()->firstOrFail();

                if ($order->isDelivered()) {
                    throw new \RuntimeException(trans('app.order_already_delivered'));
                }

                $order->fulfillment_method = Order::FULFILLMENT_METHOD_COURIER;
                $order->delivery_boy_id = null;
                $order->courier_name = $request->input('courier_name');
                $order->courier_phone = $request->input('courier_phone');
                $order->courier_tracking_number = $request->input('courier_tracking_number');
                $order->courier_added_at = now();

                // NOTE: `otp` is a non-nullable string column, so pre-existing rows
                // hold '' rather than null. `?? ` only falls back on null, so it was
                // silently keeping the empty string forever — use empty() instead.
                if (empty($order->otp)) {
                    $order->otp = Order::generateDeliveryOtp();
                }

                \Log::debug('assignCourier: otp state', [
                    'order_id' => $order->id,
                    'otp_after_assign' => $order->otp,
                ]);

                if ((int) $order->order_status_id < Order::STATUS_AWAITING_DELIVERY) {
                    $order->order_status_id = Order::STATUS_AWAITING_DELIVERY;
                }

                $order->save();
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', trans('messages.created', ['model' => $this->model_name]));
    }

    /**
     * Vendor-side alternative to the customer's "Confirm Received" tap: the
     * vendor reads the OTP back from the customer over a call/in person and
     * enters it here. Verified server-side against the same OTP shown on the
     * customer's order page, so it can't be used to bypass confirmation
     * without the code. Mirrors Api\Vendor\OrderFulfillmentController::confirm_courier_otp.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function confirmCourierOtp(Request $request, $id)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        try {
            \DB::transaction(function () use ($request, $id) {
                $order = Order::whereKey($id)->lockForUpdate()->firstOrFail();

                if ($order->isDelivered()) {
                    throw new \RuntimeException(trans('app.order_already_delivered'));
                }

                if (! $order->hasCourier()) {
                    throw new \RuntimeException(trans('app.courier_details_required'));
                }

                if (empty($order->otp) || ! hash_equals((string) $order->otp, (string) $request->input('otp'))) {
                    throw new \RuntimeException(trans('app.invalid_otp'));
                }

                $order->mark_as_goods_received();
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    /**
     * Web-panel pickup handoff: the customer reads the OTP shown on their
     * order page out to the seller in-store, and the seller enters it here.
     * Verified server-side against the same OTP the customer's app shows.
     * Mirrors Api\Vendor\OrderFulfillmentController::confirm_pickup_otp.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function confirmPickupOtp(Request $request, $id)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        try {
            \DB::transaction(function () use ($request, $id) {
                $order = Order::whereKey($id)->lockForUpdate()->firstOrFail();

                if ($order->isDelivered()) {
                    throw new \RuntimeException(trans('app.order_already_delivered'));
                }

                if (! $order->pickup()) {
                    throw new \RuntimeException(trans('app.pickup_details_required'));
                }

                if (empty($order->otp) || ! hash_equals((string) $order->otp, (string) $request->input('otp'))) {
                    throw new \RuntimeException(trans('app.invalid_otp'));
                }

                $order->mark_as_goods_received();
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    /**
     * Web-panel alternative to the delivery boy app's OTP confirmation, for
     * shop-owned riders (not couriers). Mirrors Api\DeliveryBoy\OrderController::confirmDelivery.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function confirmDeliveryBoyOtp(Request $request, $id)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        try {
            \DB::transaction(function () use ($request, $id) {
                $order = Order::whereKey($id)->lockForUpdate()->firstOrFail();

                if ($order->isDelivered()) {
                    throw new \RuntimeException(trans('app.order_already_delivered'));
                }

                if (! $order->reached_at) {
                    throw new \RuntimeException(trans('app.not_reached_yet'));
                }

                if (empty($order->otp) || ! hash_equals((string) $order->otp, (string) $request->input('otp'))) {
                    throw new \RuntimeException(trans('app.invalid_otp'));
                }

                $order->mark_as_goods_received();
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    /**
     * Fulfill the order
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function fulfill(FulfillOrderRequest $request, $id)
    {
        $order = $this->order->find($id);

        $this->authorize('fulfill', $order); // Check permission

        if ($order->isDelivered()) {
            return back()->with('error', trans('app.order_already_delivered'));
        }

        $this->order->fulfill($request, $order);

        event(new OrderFulfilled($order, $request->filled('notify_customer')));

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    /**
     * Mark a pickup order as picked up by the customer.
     *
     * Pickup orders have no delivery boy/courier/OTP flow, so this is the vendor's
     * only way to complete them — unlike deliver orders, there's no confirmation
     * step to bypass here since none exists for this fulfilment type.
     */
    public function markAsPickedUp($id)
    {
        $order = $this->order->find($id);

        $this->authorize('fulfill', $order);

        if ($order->isDelivered()) {
            return back()->with('error', trans('app.order_already_delivered'));
        }

        abort_unless($order->pickup(), 400, trans('app.order_not_pickup'));

        $order->mark_as_goods_received();

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    public function adminNote($id)
    {
        $order = $this->order->find($id);

        $this->authorize('fulfill', $order); // Check permission

        return view('admin.order._edit_admin_note', compact('order'));
    }

    public function saveAdminNote(Request $request, $id)
    {
        $order = $this->order->find($id);

        // $this->authorize('fulfill', $order); // Check permission

        $this->order->updateAdminNote($request, $order);

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    /**
     * Trash the specified resource.
     *
     * @param  Order  $order
     * @return \Illuminate\Http\RedirectResponse
     */
    public function archive(Request $request, $id)
    {
        $this->order->trash($id);

        return redirect()->route('admin.order.order.index')
            ->with('success', trans('messages.archived', ['model' => $this->model_name]));
    }

    /**
     * Restore the specified resource from soft delete.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restore(Request $request, $id)
    {
        $this->order->restore($id);

        return back()->with('success', trans('messages.restored', ['model' => $this->model_name]));
    }

    /**
     * Assign Payment Status of the given orders, Its uses the ajax middleware
     *
     * @param  \Illuminate\Http\Request  $request  Request contains ids of checked/selected orders
     * @param  string|null  $assign  The payment status to assign (paid, unpaid, refunded)
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function massAssignPaymentStatus(Request $request, $assign = null)
    {
        $orders = Order::whereIn('id', $request->ids)->get();

        foreach ($orders as $order) {
            $this->authorize('fulfill', $order);

            // Bank transfer proofs are verified by admin only — never let a
            // vendor bulk-approve one through the mass payment-status action.
            if ($assign === 'paid' && Auth::user()->isFromMerchant() && optional($order->paymentMethod)->code === 'wire') {
                continue;
            }

            switch ($assign) {
                case 'paid':
                    $order->markAsPaid();
                    break;
                case 'unpaid':
                    $order->markAsUnpaid();
                    break;
                case 'refunded':
                    $order->markAsRefunded();
                    break;
            }
        }

        if ($request->ajax()) {
            return response()->json(['success' => trans('messages.updated', ['model' => $this->model_name])]);
        }

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    /**
     * Toggle Payment Status of the given order, Its uses the ajax middleware
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function togglePaymentStatus(Request $request, $id)
    {
        $order = $this->order->find($id);

        // Bank transfer proofs are verified by admin only, regardless of the
        // vendor_get_paid_directly setting — never let a vendor self-approve one.
        if (Auth::user()->isFromMerchant() && (! vendor_get_paid_directly() || optional($order->paymentMethod)->code === 'wire')) {
            return back()->with('warning', trans('messages.failed', ['model' => $this->model_name]));
        }

        $this->authorize('fulfill', $order); // Check permission

        if ($order->isPaid()) {
            $order->markAsUnpaid();
        } else {
            $order->markAsPaid();
        }

        return back()->with('success', trans('messages.updated', ['model' => $this->model_name]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, $id)
    {
        $this->order->destroy($id);

        return back()->with('success', trans('messages.deleted', ['model' => $this->model_name]));
    }

    /**
     * Empty the Trash the mass resources.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function emptyTrash(Request $request)
    {
        $this->order->emptyTrash($request);

        if ($request->ajax()) {
            return response()->json(['success' => trans('messages.deleted', ['model' => $this->model_name])]);
        }

        return back()->with('success', trans('messages.deleted', ['model' => $this->model_name]));
    }

    /**
     * Get the unique folder name for invoice
     *
     * @return string
     */
    private function getUniqueFolderNameForInvoice()
    {
        return Auth::user()->isFromMerchant() ? 'merchant'.Auth::user()->merchantId().'shop'.Auth::user()->shop->id : 'admin';
    }
}
