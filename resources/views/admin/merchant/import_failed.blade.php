@extends('admin.layouts.master')

@section('content')
  <div class="alert alert-danger">
    <strong><i class="icon fa fa-info-circle"></i>{{ trans('app.notice') }}</strong>
    {{ trans('messages.import_ignored') }}
  </div>

  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">{{ trans('app.import_failed') }}</h3>
      <div class="box-tools pull-right">
        @if (Auth::user()->isAdmin())
          <a href="javascript:void(0)" data-link="{{ route('admin.vendor.merchant.bulk') }}" class="ajax-modal-btn btn btn-default btn-flat">{{ trans('app.bulk_import') }}</a>
        @endif
      </div>
    </div> <!-- /.box-header -->

    <div class="box-body responsive-table">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>{{ trans('app.logo') }}</th>
            <th class="text-center">{{ trans('app.merchant') }}</th>
            <th class="text-center">{{ trans('app.billing') }}</th>
            <th class="text-center">{{ trans('app.details') }}</th>
            <th width="20%">{{ trans('app.reason') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($failed_rows as $row)
            <tr>
              <td>
                <img src="{{ $row[0]['data']['brand_logo'] ?? get_placeholder_img('small') }}" class="img-sm">
              </td>
              <td>
                <dl>
                  <dt>{{ trans('app.name') }}: </dt>
                  <dd>{{ $row[0]['data']['name'] }}</dd>
                  <dt>{{ trans('app.email') }}: </dt>
                  <dd>{{ $row[0]['data']['email'] }}</dd>
                  <dt>{{ trans('app.shop_name') }}: </dt>
                  <dd>{{ $row[0]['data']['shop_name'] }}</dd>
                  <dt>{{ trans('app.legal_name') }}: </dt>
                  <dd>{{ $row[0]['data']['legal_name'] }}</dd>
                  @if ($row[0]['data']['external_url'])
                    <dt>{{ trans('app.external_url') }}: </dt>
                    <dd>{{ $row[0]['data']['external_url'] }}</dd>
                  @endif
                  <dt>{{ trans('app.support_phone') }}: </dt>
                  <dd>{{ $row[0]['data']['support_phone'] }}</dd>
                  @if ($row[0]['data']['support_phone_toll_free'])
                    <dt>{{ trans('app.support_phone_toll_free') }}: </dt>
                    <dd>{{ $row[0]['data']['support_phone_toll_free'] }}</dd>
                  @endif
                  <dt>{{ trans('app.support_email') }}: </dt>
                  <dd>{{ $row[0]['data']['support_email'] }}</dd>
                  <dt>{{ trans('app.default_sender_email_address') }}: </dt>
                  <dd>{{ $row[0]['data']['default_sender_email_address'] }}</dd>
                  <dt>{{ trans('app.default_email_sender_name') }}: </dt>
                  <dd>{{ $row[0]['data']['default_email_sender_name'] ?? '' }}</dd>
                </dl>
              </td>
              <td>
                {{ $row[0]['data']['current_billing_plan'] }}
                <dl>
                  @if (isset($row[0]['data']['commission_rate']) && $row[0]['data']['commission_rate'] && is_incevio_package_loaded('dynamicCommission'))
                    <dt>{{ trans('app.commission_rate') }}: </dt>
                    <dd>{{ $row[0]['data']['commission_rate'] }}</dd>
                  @endif
                  <dt>{{ trans('app.billing_starts_at') }}: </dt>
                  <dd>{{ $row[0]['data']['billing_starts_at'] ?? trans('app.now') }}</dd>
                  <dt>{{ trans('app.trial_ends_at') }}: </dt>
                  <dd>{{ $row[0]['data']['trial_ends_at'] }}</dd>
                </dl>
              </td>
              <td>
                <dl>
                  @if ($row[0]['data']['total_item_sold'])
                    <dt>{{ trans('app.total_item_sold') }}: </dt>
                    <dd>{{ $row[0]['data']['total_item_sold'] }}</dd>
                  @endif

                  @if ($row[0]['data']['total_sold_amount'])
                    <dt>{{ trans('app.total_sold_amount') }}: </dt>
                    <dd>{{ $row[0]['data']['total_sold_amount'] }}</dd>
                  @endif

                  @if ($row[0]['data']['order_handling_cost'])
                    <dt>{{ trans('app.order_handling_cost') }}: </dt>
                    <dd>{{ $row[0]['data']['order_handling_cost'] }}</dd>
                  @endif

                  @if ($row[0]['data']['credit_back_percentage'])
                    <dt>{{ trans('app.credit_back_percentage') }}: </dt>
                    <dd>{{ $row[0]['data']['credit_back_percentage'] }}</dd>
                  @endif

                  @if ($row[0]['data']['alert_quantity'])
                    <dt>{{ trans('app.alert_quantity') }}: </dt>
                    <dd>{{ $row[0]['data']['alert_quantity'] }}</dd>
                  @endif

                  <dt>{{ trans('app.enable_live_chat') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row[0]['data']['enable_live_chat'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i>
                  </dd>

                  <dt>{{ trans('app.pickup_enabled') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row[0]['data']['pickup_enabled'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i>
                  </dd>

                  <dt>{{ trans('app.payment_verified') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row[0]['data']['payment_verified'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i>
                  </dd>

                  <dt>{{ trans('app.id_verified') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row[0]['data']['id_verified'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i>
                  </dd>

                  <dt>{{ trans('app.phone_verified') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row[0]['data']['phone_verified'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i>
                  </dd>

                  <dt>{{ trans('app.address_verified') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row[0]['data']['address_verified'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i>
                  </dd>

                  <dt>{{ trans('app.auto_archive_order') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row[0]['data']['auto_archive_order'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i>
                  </dd>

                  <dt>{{ trans('app.notify_new_message') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row[0]['data']['notify_new_message'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i>
                  </dd>

                  <dt>{{ trans('app.notify_alert_quantity') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row[0]['data']['notify_alert_quantity'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i>
                  </dd>

                  <dt>{{ trans('app.notify_inventory_out') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row[0]['data']['notify_inventory_out'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i>
                  </dd>

                  <dt>{{ trans('app.notify_new_order') }}: </dt>
                  <dd>
                    <i class="fa fa-{{ $row[0]['data']['notify_new_order'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i>
                  </dd>

                  <dt>{{ trans('app.active') }}: </dt>
                  <dd><i class="fa fa-{{ $row[0]['data']['active'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i></dd>

                  <dt>{{ trans('app.maintenance_mode') }}: </dt>
                  <dd><i class="fa fa-{{ $row[0]['data']['maintenance_mode'] == 'TRUE' ? 'check' : 'times' }} text-muted"></i></dd>
                </dl>
              </td>
              <td><span class="label label-danger">{{ $row[0]['reason'] }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div> <!-- /.box-body -->

    <div class="box-footer">
      <a href="{{ route('admin.vendor.merchant.index') }}" class="btn btn-danger btn-flat">{{ trans('app.dismiss') }}</a>

      <div class="box-tools pull-right">
        {!! Form::open(['route' => 'admin.vendor.merchant.downloadFailedRows', 'id' => 'form', 'class' => 'inline-form', 'data-toggle' => 'validator']) !!}

        @foreach ($failed_rows as $row)
          <input type="hidden" name="data[]" value="{{ serialize($row[0]['data']) }}">
        @endforeach

        {!! Form::button(trans('app.download_failed_rows'), ['type' => 'submit', 'class' => 'btn btn-new btn-flat']) !!}
        {!! Form::close() !!}
      </div>
    </div> <!-- /.box-footer -->
  </div> <!-- /.box -->
@endsection
