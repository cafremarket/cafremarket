<?php

namespace App\Http\Controllers\Admin;

use App\Events\System\SystemConfigUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\UpdateSystemConfigRequest;
use App\Models\PaymentMethod;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\Gate;

class SystemConfigController extends Controller
{
    private $model_name;

    private const PAGES = [
        'basic' => [
            'label' => 'app.basic_settings',
            'icon' => 'fa-cubes',
            'desc' => 'app.basic_settings',
        ],
        'payment' => [
            'label' => 'app.payment_methods',
            'icon' => 'fa-credit-card',
            'desc' => 'app.payment_methods',
        ],
        'shipping' => [
            'label' => 'app.shipping',
            'icon' => 'fa-truck',
            'desc' => 'app.shipping',
        ],
        'support' => [
            'label' => 'app.support',
            'icon' => 'fa-phone',
            'desc' => 'app.support',
        ],
        'websocket' => [
            'label' => 'app.websocket',
            'icon' => 'fa-plug',
            'desc' => 'app.websocket',
        ],
        'notifications' => [
            'label' => 'app.notifications',
            'icon' => 'fa-bell-o',
            'desc' => 'app.notifications',
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        $this->model_name = trans('app.model.config');
    }

    /**
     * Settings hub.
     */
    public function view()
    {
        $system = SystemConfig::orderBy('id', 'asc')->first();

        $this->authorize('view', $system);

        return view('admin.system.config.index', [
            'system' => $system,
            'cards' => $this->hubCards(),
            'navItems' => $this->navItems(),
            'active' => 'hub',
        ]);
    }

    /**
     * Settings subpage.
     */
    public function page(string $page)
    {
        if (! isset(self::PAGES[$page])) {
            abort(404);
        }

        $system = SystemConfig::orderBy('id', 'asc')->first();

        $this->authorize('view', $system);

        return view('admin.system.config.page', [
            'system' => $system,
            'page' => $page,
            'pageMeta' => self::PAGES[$page],
            'navItems' => $this->navItems(),
            'active' => $page,
            'can_update' => Gate::allows('update', $system),
        ]);
    }

    public function update(UpdateSystemConfigRequest $request)
    {
        if (config('app.demo') == true) {
            return response('error', 444);
        }

        $system = SystemConfig::orderBy('id', 'asc')->first();

        $this->authorize('update', $system);

        if ($system->update($request->all())) {
            event(new SystemConfigUpdated($system));

            return response('success', 200);
        }

        return response('error', 405);
    }

    public function togglePaymentMethod(UpdateSystemConfigRequest $request, $id)
    {
        if (config('app.demo') == true) {
            return response('error', 444);
        }

        $system = SystemConfig::orderBy('id', 'asc')->first();

        $this->authorize('update', $system);

        $paymentMethod = PaymentMethod::findOrFail($id);

        if ($paymentMethod->code === 'stripe') {
            return response('error', 405);
        }

        $paymentMethod->enabled = ! $paymentMethod->enabled;

        if ($paymentMethod->save()) {
            event(new SystemConfigUpdated($system));

            return response('success', 200);
        }

        return response('error', 405);
    }

    public function toggleShippingMethod(UpdateSystemConfigRequest $request, $id)
    {
        if (config('app.demo') == true) {
            return response('error', 444);
        }

        $system = SystemConfig::orderBy('id', 'asc')->first();

        $this->authorize('update', $system);

        $shippingMethod = \App\Models\ShippingMethod::findOrFail($id);

        $shippingMethod->enabled = ! $shippingMethod->enabled;

        if ($shippingMethod->save()) {
            event(new SystemConfigUpdated($system));

            return response('success', 200);
        }

        return response('error', 405);
    }

    public function toggleConfig(UpdateSystemConfigRequest $request, $node)
    {
        if (config('app.demo') == true) {
            return response('error', 444);
        }

        $system = SystemConfig::orderBy('id', 'asc')->first();

        $this->authorize('update', $system);

        if ($node === 'vendor_can_view_customer_info') {
            $system->vendor_can_view_customer_info = true;
            $system->save();
            event(new SystemConfigUpdated($system));

            return response('success', 200);
        }

        $system->$node = ! $system->$node;

        if ($system->save()) {
            event(new SystemConfigUpdated($system));

            return response('success', 200);
        }

        return response('error', 405);
    }

    private function navItems(): array
    {
        $items = [
            [
                'key' => 'hub',
                'label' => 'Overview',
                'icon' => 'fa-th-large',
                'url' => route('admin.setting.system.config'),
            ],
        ];

        foreach (self::PAGES as $key => $meta) {
            $items[] = [
                'key' => $key,
                'label' => trans($meta['label']),
                'icon' => $meta['icon'],
                'url' => route('admin.setting.system.config.page', $key),
            ];
        }

        return $items;
    }

    private function hubCards(): array
    {
        $cards = [];

        foreach (self::PAGES as $key => $meta) {
            $cards[] = [
                'url' => route('admin.setting.system.config.page', $key),
                'icon' => $meta['icon'],
                'title' => trans($meta['label']),
                'desc' => trans($meta['desc']),
            ];
        }

        return $cards;
    }
}
