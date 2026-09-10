
          <div class="row">
            <div class="col-sm-8 col-sm-offset-2">
              @include('merchant.config.partials._toggle_row', [
                'field' => 'active_ecommerce',
                'label' => trans('app.active_ecommerce'),
                'help' => trans('help.active_ecommerce'),
                'active' => $config->active_ecommerce == 1,
              ])

              @include('merchant.config.partials._toggle_row', [
                'field' => 'show_shop_desc_with_listing',
                'label' => trans('app.show_shop_desc_with_listing'),
                'help' => trans('help.show_shop_desc_with_listing'),
                'active' => $config->show_shop_desc_with_listing == 1,
              ])

              @include('merchant.config.partials._toggle_row', [
                'field' => 'show_refund_policy_with_listing',
                'label' => trans('app.show_refund_policy_with_listing'),
                'help' => trans('help.show_refund_policy_with_listing'),
                'active' => $config->show_refund_policy_with_listing == 1,
              ])
            </div>
          </div>
