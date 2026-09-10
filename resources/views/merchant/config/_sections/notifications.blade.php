
          <div class="row mb-5">
            <div class="col-sm-8 col-sm-offset-2">
              <fieldset>
                <legend>{{ trans('app.order') }}</legend>

                @include('merchant.config.partials._toggle_row', [
                  'field' => 'notify_new_order',
                  'label' => trans('app.notify_new_order'),
                  'help' => trans('help.notify_new_order'),
                  'active' => $config->notify_new_order == 1,
                ])

                @include('merchant.config.partials._toggle_row', [
                  'field' => 'notify_abandoned_checkout',
                  'label' => trans('app.notify_abandoned_checkout'),
                  'help' => trans('help.notify_abandoned_checkout'),
                  'active' => $config->notify_abandoned_checkout == 1,
                ])

                @include('merchant.config.partials._toggle_row', [
                  'field' => 'notify_new_disput',
                  'label' => trans('app.notify_new_dispute'),
                  'help' => trans('help.notify_new_dispute'),
                  'active' => $config->notify_new_disput == 1,
                ])
              </fieldset>

              @if (is_catalog_enabled())
                <fieldset>
                  <legend>{{ trans('app.inventory') }}</legend>

                  @include('merchant.config.partials._toggle_row', [
                    'field' => 'notify_alert_quantity',
                    'label' => trans('app.notify_alert_quantity'),
                    'help' => trans('help.notify_alert_quantity'),
                    'active' => $config->notify_alert_quantity == 1,
                  ])

                  @include('merchant.config.partials._toggle_row', [
                    'field' => 'notify_inventory_out',
                    'label' => trans('app.notify_inventory_out'),
                    'help' => trans('help.notify_inventory_out'),
                    'active' => $config->notify_inventory_out == 1,
                  ])
                </fieldset>
              @endif
            </div>
          </div>
        </div>
