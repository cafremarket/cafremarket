@component('mail::panel')
<table class="mail-meta" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="mail-meta__label">{{ trans('messages.shop_name') }}</td>
<td class="mail-meta__value">{{ $dispute_detail->shop->name }}</td>
</tr>
<tr>
<td class="mail-meta__label">{{ trans('messages.customer_name') }}</td>
<td class="mail-meta__value">{{ $dispute_detail->customer->getName() }}</td>
</tr>
<tr>
<td class="mail-meta__label">{{ trans('messages.order_id') }}</td>
<td class="mail-meta__value">{{ $dispute_detail->order->order_number }}</td>
</tr>
<tr>
<td class="mail-meta__label">{{ trans('messages.status') }}</td>
<td class="mail-meta__value"><strong>{!! $dispute_detail->statusName() !!}</strong></td>
</tr>
</table>
@endcomponent
