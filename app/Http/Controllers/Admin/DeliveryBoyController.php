<?php

namespace App\Http\Controllers\Admin;

use App\Common\Authorizable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\CreateDeliveryBoyRequest;
use App\Http\Requests\Validations\ResetDeliveryBoyPasswordRequest;
use App\Http\Requests\Validations\UpdateDeliveryBoyRequest;
use App\Models\DeliveryBoy;
use App\Repositories\DeliveryBoy\DeliveryBoyRepository;
use Illuminate\Http\Request;

class DeliveryBoyController extends Controller
{
    use Authorizable;

    private $model_name;

    private $deliveryBoy;

    /**
     * construct
     */
    public function __construct(DeliveryBoyRepository $deliveryBoy)
    {
        parent::__construct();

        $this->model_name = trans('app.model.delivery_boy');
        $this->deliveryBoy = $deliveryBoy;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $deliveryBoys = $this->deliveryBoy->all();

        return view('admin.deliveryboy.index', compact('deliveryBoys'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.deliveryboy._create');
    }

    /**
     * Whether a delivery boy account already exists for this email (in any
     * store) — used by the create form to hide the password field and reuse
     * that rider's existing password instead of asking for a new one.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkEmail(Request $request)
    {
        $exists = DeliveryBoy::where('email', $request->get('email'))->exists();

        return response()->json(['exists' => $exists]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateDeliveryBoyRequest $request)
    {
        $this->deliveryBoy->store($request);

        return back()->with('success', trans('messages.created', ['model' => $this->model_name]));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $deliveryboy = $this->deliveryBoy->find($id);

        return view('admin.deliveryboy._show', compact('deliveryboy'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(DeliveryBoy $deliveryboy)
    {
        return view('admin.deliveryboy._edit', compact('deliveryboy'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDeliveryBoyRequest $request, DeliveryBoy $deliveryboy)
    {
        if (config('app.demo') == true && $deliveryboy->id <= config('system.demo.delivery_boys')) {
            return response()->json(['message' => trans('messages.demo_restriction')], 400);
        }

        $this->deliveryBoy->update($request, $deliveryboy->id);

        return back()->with('success', trans('messages.created', ['model' => $this->model_name]));
    }

    /**
     * Show the reset-password form for a delivery boy.
     *
     * @return \Illuminate\View\View
     */
    public function resetPasswordForm(DeliveryBoy $deliveryboy)
    {
        return view('admin.deliveryboy._reset_password', compact('deliveryboy'));
    }

    /**
     * Set a new password for a delivery boy (admin/vendor-initiated reset —
     * doesn't require knowing the old password).
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetPassword(ResetDeliveryBoyPasswordRequest $request, DeliveryBoy $deliveryboy)
    {
        $deliveryboy->password = $request->password;
        $deliveryboy->save();

        return back()->with('success', trans('messages.password_reset', ['model' => $this->model_name]));
    }

    /**
     * Permanently remove the specified resource from storage — there is no
     * trash/restore step for delivery boys, this deletes it outright.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        if (config('app.demo') == true && $id <= config('system.demo.delivery_boys')) {
            return response()->json(['message' => trans('messages.demo_restriction')], 400);
        }

        $this->deliveryBoy->destroy($id);

        return back()->with('success', trans('messages.deleted', ['model' => $this->model_name]));
    }

    /**
     * Destroy the mass resources — no trash/restore step, deletes outright.
     *
     * @return \Illuminate\Http\Response
     */
    public function massDestroy(Request $request)
    {
        if (config('app.demo') == true) {
            return back()->with('warning', trans('messages.demo_restriction'));
        }

        $this->deliveryBoy->massDestroy($request->ids);

        if ($request->ajax()) {
            return response()->json([
                'success' => trans('messages.deleted', ['model' => $this->model_name]),
            ]);
        }

        return back()->with('success', trans('messages.deleted', ['model' => $this->model_name]));
    }
}
