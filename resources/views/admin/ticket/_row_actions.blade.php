@can('reply', $ticket)
  <a href="javascript:void(0)" data-link="{{ route('admin.support.ticket.reply', $ticket) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.reply') }}" data-toggle="tooltip"><i class="fa fa-reply"></i></a>
@endcan
@can('update', $ticket)
  <a href="javascript:void(0)" data-link="{{ route('admin.support.ticket.edit', $ticket->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.update') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
@endcan
@can('assign', $ticket)
  <a href="javascript:void(0)" data-link="{{ route('admin.support.ticket.showAssignForm', $ticket->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.assign') }}" data-toggle="tooltip"><i class="fa fa-hashtag"></i></a>
@endcan
