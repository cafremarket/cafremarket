<a href="{{ url('admin/setting/system/general') }}">
  <i class="fa fa-envelope text-red"></i>&nbsp;
  <strong>{{ trans('notifications.mail_delivery_failed.title') }}</strong>
  — {{ trans('notifications.mail_delivery_failed.message') }}
  @if (!empty($notification->data['order_id']))
    ({{ trans('notifications.mail_delivery_failed.order') }} #{{ $notification->data['order_id'] }})
  @endif
</a>
