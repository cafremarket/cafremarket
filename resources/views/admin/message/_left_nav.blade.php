<div class="admin-mailbox-nav">
  <a href="javascript:void(0)" data-link="{{ route('admin.support.message.create') }}" class="ajax-modal-btn btn btn-new btn-block admin-mailbox-nav__compose">
    <i class="fa fa-pencil"></i> {{ trans('app.compose') }}
  </a>

  <a href="javascript:void(0)" data-link="{{ route('admin.support.message.create', 'template') }}" class="ajax-modal-btn btn btn-default btn-block admin-mailbox-nav__template">
    <i class="fa fa-file-text-o"></i> {{ trans('app.new_message_with_template') }}
  </a>

  <div class="admin-mailbox-nav__folders">
    <div class="admin-mailbox-nav__folders-title">{{ trans('app.folders') }}</div>
    <ul class="admin-mailbox-nav__list">
      <li class="{{ Request::is('*/labelOf/' . \App\Models\Message::LABEL_INBOX . '*') || (isset($message) && $message->label == \App\Models\Message::LABEL_INBOX) ? 'active' : '' }}">
        <a href="{{ route('admin.support.message.labelOf', \App\Models\Message::LABEL_INBOX) }}">
          <i class="fa fa-inbox"></i> {{ trans('app.inbox') }}
          @if ($unread_msg_count = \App\Helpers\Statistics::unread_msg_count())
            <span class="label label-primary">{{ $unread_msg_count }}</span>
          @endif
        </a>
      </li>
      <li class="{{ Request::is('*/labelOf/' . \App\Models\Message::LABEL_SENT . '*') || (isset($message) && $message->label == \App\Models\Message::LABEL_SENT) ? 'active' : '' }}">
        <a href="{{ route('admin.support.message.labelOf', \App\Models\Message::LABEL_SENT) }}">
          <i class="fa fa-envelope-o"></i> {{ trans('app.sent') }}
        </a>
      </li>
      <li class="{{ Request::is('*/labelOf/' . \App\Models\Message::LABEL_DRAFT . '*') || (isset($message) && $message->label == \App\Models\Message::LABEL_DRAFT) ? 'active' : '' }}">
        <a href="{{ route('admin.support.message.labelOf', \App\Models\Message::LABEL_DRAFT) }}">
          <i class="fa fa-file-text-o"></i> {{ trans('app.drafts') }}
          @if ($draft_msg_count = \App\Helpers\Statistics::draft_msg_count())
            <span class="label label-default">{{ $draft_msg_count }}</span>
          @endif
        </a>
      </li>
      <li class="{{ Request::is('*/labelOf/' . \App\Models\Message::LABEL_SPAM . '*') || (isset($message) && $message->label == \App\Models\Message::LABEL_SPAM) ? 'active' : '' }}">
        <a href="{{ route('admin.support.message.labelOf', \App\Models\Message::LABEL_SPAM) }}">
          <i class="fa fa-filter"></i> {{ trans('app.spams') }}
          @if ($spam_msg_count = \App\Helpers\Statistics::spam_msg_count())
            <span class="label label-warning">{{ $spam_msg_count }}</span>
          @endif
        </a>
      </li>
      <li class="{{ Request::is('*/labelOf/' . \App\Models\Message::LABEL_TRASH . '*') || (isset($message) && $message->label == \App\Models\Message::LABEL_TRASH) ? 'active' : '' }}">
        <a href="{{ route('admin.support.message.labelOf', \App\Models\Message::LABEL_TRASH) }}">
          <i class="fa fa-trash-o"></i> {{ trans('app.trash') }}
          @if (($trash_msg_count = \App\Helpers\Statistics::trash_msg_count()) && $trash_msg_count > 10)
            <span class="label label-danger">{{ $trash_msg_count }}</span>
          @endif
        </a>
      </li>
    </ul>
  </div>
</div>
