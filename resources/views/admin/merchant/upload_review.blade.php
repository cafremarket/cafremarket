@extends('admin.layouts.master')

@section('content')
  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">
        {{ trans('app.preview') }} <small>({{ trans('app.total_number_of_rows', ['value' => count($rows)]) }})</small>
      </h3>
      <div class="box-tools pull-right">
        @if (Auth::user()->isAdmin())
          <a href="javascript:void(0)" data-link="{{ route('admin.vendor.merchant.bulk') }}" class="ajax-modal-btn btn btn-default btn-flat">{{ trans('app.bulk_import') }}</a>
        @endif
      </div>
    </div> <!-- /.box-header -->

    {!! Form::open(['route' => 'admin.vendor.merchant.import', 'id' => 'form', 'class' => 'inline-form', 'data-toggle' => 'validator']) !!}
    <div class="box-body responsive-table">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>{{ trans('app.logo') }}</th>
            <th class="text-center">{{ trans('app.merchant') }}</th>
            <th class="text-center">{{ trans('app.billing') }}</th>
            <th class="text-center">{{ trans('app.details') }}</th>
          </tr>
        </thead>
        <tbody>
          @php
            $serializeData = [];
          @endphp

          @foreach ($rows as $row)
            @php
              $slug = $row['slug'] ?? convertToSlugString($row['shop_name']);
            @endphp

            {{ Form::hidden('data[]', serialize($row)) }}
            <tr>
              <td>
                <img src="{{ $row['brand_logo'] ?? get_placeholder_img('small') }}" class="img-sm">
              </td>
              <td>
                <dl>
                  <dt>{{ trans('app.name') }}: </dt>
                  <dd>{{ $row['name'] }}</dd>
                  <dt>{{ trans('app.email') }}: </dt>
                  <dd>{{ $row['email'] }}</dd>
                  <dt>{{ trans('app.shop_name') }}: </dt>
                  <dd>{{ $row['shop_name'] }}</dd>
                  <dt>{{ trans('app.legal_name') }}: </dt>
                  <dd>{{ $row['legal_name'] }}</dd>
                  @if ($row['external_url'])
                    <dt>{{ trans('app.external_url') }}: </dt>
                    <dd>{{ $row['external_url'] }}</dd>
                  @endif
                  <dt>{{ trans('app.support_phone') }}: </dt>
                  <dd>{{ $row['support_phone'] }}</dd>
                  @if ($row['support_phone_toll_free'])
                    <dt>{{ trans('app.support_phone_toll_free') }}: </dt>
                    <dd>{{ $row['support_phone_toll_free'] }}</dd>
                  @endif
                  <dt>{{ trans('app.support_email') }}: </dt>
                  <dd>{{ $row['support_email'] }}</dd>
                  <dt>{{ trans('app.default_sender_email_address') }}: </dt>
                  <dd>{{ $row['default_sender_email_address'] }}</dd>
                  <dt>{{ trans('app.default_email_sender_name') }}: </dt>
                  <dd>{{ $row['default_email_sender_name'] }}</dd>
                </dl>
              </td>
              <td>
                {{ $row['current_billing_plan'] }}

                <dl>
                  @if (isset($row['commission_rate']) && $row['commission_rate'] && is_incevio_package_loaded('dynamicCommission'))
                    <dt>{{ trans('packages.dynamicCommission.commission_rate') }}: </dt>
                    <dd>{{ $row['commission_rate'] }}</dd>
                  @endif
                  <dt>{{ trans('app.billing_starts_at') }}: </dt>
                  <dd>{{ $row['billing_starts_at'] ?? trans('app.now') }}</dd>
                  <dt>{{ trans('app.trial_ends_at') }}: </dt>
                  <dd>{{ $row['trial_ends_at'] }}</dd>
                </dl>
              </td>
              <td>
                <dl>
                  @if ($row['total_item_sold'])
                    <dt>{{ trans('app.total_item_sold') }}: </dt>
                    <dd>{{ $row['total_item_sold'] }}</dd>
                  @endif
                  @if ($row['total_sold_amount'])
                    <dt>{{ trans('app.total_sold_amount') }}: </dt>
                    <dd>{{ $row['total_sold_amount'] }}</dd>
                  @endif
                  @if ($row['order_handling_cost'])
                    <dt>{{ trans('app.order_handling_cost') }}: </dt>
                    <dd>{{ $row['order_handling_cost'] }}</dd>
                  @endif
                  @if ($row['credit_back_percentage'])
                    <dt>{{ trans('app.credit_back_percentage') }}: </dt>
                    <dd>{{ $row['credit_back_percentage'] }}</dd>
                  @endif
                  @if ($row['alert_quantity'])
                    <dt>{{ trans('app.alert_quantity') }}: </dt>
                    <dd>{{ $row['alert_quantity'] }}</dd>
                  @endif
                  <dt>{{ trans('app.enable_live_chat') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row['enable_live_chat'] == 1 ? 'check' : 'times' }} text-muted"></i>
                  </dd>
                  <dt>{{ trans('app.pickup_enabled') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row['pickup_enabled'] == 1 ? 'check' : 'times' }} text-muted"></i>
                  </dd>
                  <dt>{{ trans('app.payment_verified') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row['payment_verified'] == 1 ? 'check' : 'times' }} text-muted"></i>
                  </dd>
                  <dt>{{ trans('app.id_verified') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row['id_verified'] == 1 ? 'check' : 'times' }} text-muted"></i>
                  </dd>
                  <dt>{{ trans('app.phone_verified') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row['phone_verified'] == 1 ? 'check' : 'times' }} text-muted"></i>
                  </dd>
                  <dt>{{ trans('app.address_verified') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row['address_verified'] == 1 ? 'check' : 'times' }} text-muted"></i>
                  </dd>
                  <dt>{{ trans('app.show_refund_policy_with_listing') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row['show_refund_policy_with_listing'] == 1 ? 'check' : 'times' }} text-muted"></i>
                  </dd>
                  <dt>{{ trans('app.auto_archive_order') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row['auto_archive_order'] == 1 ? 'check' : 'times' }} text-muted"></i>
                  </dd>
                  <dt>{{ trans('app.notify_new_message') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row['notify_new_message'] == 1 ? 'check' : 'times' }} text-muted"></i>
                  </dd>

                  <dt>{{ trans('app.notify_alert_quantity') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row['notify_alert_quantity'] == 1 ? 'check' : 'times' }} text-muted"></i>
                  </dd>
                  <dt>{{ trans('app.notify_inventory_out') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row['notify_inventory_out'] == 1 ? 'check' : 'times' }} text-muted"></i>
                  </dd>
                  <dt>{{ trans('app.notify_new_order') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row['notify_new_order'] == 1 ? 'check' : 'times' }} text-muted"></i>
                  </dd>
                  <dt>{{ trans('app.active') }}: </dt>
                  <dd><i class="fa fa-{{ $row['active'] == 1 ? 'check' : 'times' }} text-muted"></i></dd>
                  <dt>{{ trans('app.maintenance_mode') }}: </dt>
                  <dd><i class="fa fa-{{ $row['maintenance_mode'] == 1 ? 'check' : 'times' }} text-muted"></i></dd>
                </dl>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div> <!-- /.box-body -->

    <div class="box-footer my-4">
      <a href="{{ route('admin.vendor.merchant.index') }}" class="btn btn-default btn-flat">{{ trans('app.cancel') }}</a>

      <small class="indent20">{{ trans('app.total_number_of_rows', ['value' => count($rows)]) }}</small>

      <div class="box-tools pull-right">
        {!! Form::button(trans('app.looks_good'), ['type' => 'submit', 'class' => 'confirm btn btn-new btn-flat']) !!}
      </div>
    </div> <!-- /.box-footer -->
    {!! Form::close() !!}
  </div> <!-- /.box -->
@endsection
