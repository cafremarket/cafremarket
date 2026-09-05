@component('mail::panel')
<table class="mail-meta" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="mail-meta__label">{{ trans('messages.ticket_id') }}</td>
<td class="mail-meta__value">#{{ $ticket_detail->id }}</td>
</tr>
<tr>
<td class="mail-meta__label">{{ trans('messages.category') }}</td>
<td class="mail-meta__value">{{ $ticket_detail->category->name }}</td>
</tr>
<tr>
<td class="mail-meta__label">{{ trans('messages.subject') }}</td>
<td class="mail-meta__value">{{ $ticket_detail->subject }}</td>
</tr>
<tr>
<td class="mail-meta__label">{{ trans('messages.priority') }}</td>
<td class="mail-meta__value">{!! $ticket_detail->priorityLevel() !!}</td>
</tr>
<tr>
<td class="mail-meta__label">{{ trans('messages.status') }}</td>
<td class="mail-meta__value">{!! $ticket_detail->statusName() !!}</td>
</tr>
</table>
@endcomponent
