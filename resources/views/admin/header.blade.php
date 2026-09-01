<!-- Main Header -->
<header class="main-header admin-topbar">
  <!-- Logo -->
  <a href="{{ url('admin/dashboard') }}" class="logo">
    <span class="logo-mini"><img src="{{ get_icon_url('system', 'tiny') }}" alt="" style="height:22px;width:22px;border-radius:4px;"></span>
    <span class="logo-lg"><img src="{{ get_logo_url('system', 'logo') }}" alt="{{ get_site_title() }}" style="height:32px;max-width:160px;vertical-align:middle;margin-right:8px;">{{ get_site_title() }}</span>
  </a>

  <!-- Header Navbar -->
  <nav class="navbar navbar-static-top" role="navigation">

    {{-- Left zone: toggle + quick links --}}
    <div class="topbar-left">
      <a href="javascript:void(0)" class="sidebar-toggle" data-toggle="offcanvas" role="button" title="{{ trans('app.toggle_navigation') }}">
        <span class="sr-only">{{ trans('app.toggle_navigation') }}</span>
        <i class="fa fa-bars"></i>
      </a>

      <div class="topbar-quick-links hidden-xs">
        <a href="{{ url('admin/dashboard') }}" class="topbar-link {{ Request::is('admin/dashboard*') ? 'active' : '' }}">
          <i class="fa fa-home"></i>
        </a>
        <a href="{{ get_shop_url() }}" target="_blank" class="topbar-link" title="{{ trans('app.store_front') }}">
          <i class="fa fa-external-link"></i>
          <span>{{ trans('app.store_front') }}</span>
        </a>

        @if (Auth::user()->isMerchant() && !customer_can_register())
          @if (Request::session()->has('need_customer_acc'))
            <a href="{{ route('admin.merchant.createCustomer') }}" class="topbar-link topbar-link-primary">
              <i class="fa fa-user-plus"></i>
              <span>{{ trans('app.create_customer_acc') }}</span>
            </a>
          @else
            <a href="{{ route('admin.merchant.switchToCustomer') }}" class="topbar-link">
              <i class="fa fa-dashboard"></i>
              <span>{{ trans('app.view_customer_dashboard') }}</span>
            </a>
          @endif
        @endif
      </div>
    </div>

    {{-- Center: page search (filters tables on current page) --}}
    <div class="topbar-center hidden-xs hidden-sm">
      <div class="topbar-search-wrap">
        <i class="fa fa-search topbar-search-icon"></i>
        <input type="text" id="topbar-page-search" class="topbar-search-input" placeholder="{{ trans('app.search') ?? 'Search this page...' }}" autocomplete="off">
        <button type="button" class="topbar-search-clear" id="topbar-search-clear" title="Clear">&times;</button>
      </div>
    </div>

    {{-- Right zone --}}
    <div class="navbar-custom-menu">
      <ul class="nav navbar-nav topbar-nav">

        {{-- Wallet pill (merchant) --}}
        @desktop
        @if (Auth::user()->isMerchant() && is_incevio_package_loaded('wallet'))
          <li class="topbar-wallet">
            <a href="{{ route('merchant.wallet') }}">
              <span class="topbar-wallet-label">{{ trans('packages.wallet.balance') }}</span>
              <span class="topbar-wallet-amount">{{ get_formated_currency(Auth::user()->shop->balance, 2, config('system_settings.currency.id')) }}</span>
            </a>
          </li>
        @endif
        @enddesktop

        {{-- Icon group --}}
        <li class="topbar-divider hidden-xs"></li>

        {{-- Messages --}}
        <li class="dropdown messages-menu">
          <a href="javascript:void(0)" class="dropdown-toggle topbar-icon-btn" data-toggle="dropdown" title="{{ trans('nav.support_messages') }}">
            <i class="fa fa-envelope-o"></i>
            @if ($count_message = $unread_messages->count())
              <span class="topbar-badge topbar-badge-success">{{ $count_message > 9 ? '9+' : $count_message }}</span>
            @endif
          </a>
          <ul class="dropdown-menu topbar-dropdown">
            <li class="header">{{ trans('messages.message_count', ['count' => $count_message]) }}</li>
            <li>
              <ul class="menu topbar-dropdown-list">
                @forelse($unread_messages as $message)
                  @continue($loop->index > 5)
                  <li>
                    <a href="{{ route('admin.support.message.show', $message) }}">
                      <div class="pull-left">
                        <img src="{{ get_avatar_src($message->customer, 'tiny') }}" class="img-circle" alt="">
                      </div>
                      <div class="topbar-dropdown-body">
                        <h4>{!! $message->subject !!} <small>{{ $message->created_at->diffForHumans() }}</small></h4>
                        <p>{{ strip_tags(Str::limit($message->message, 80)) }}</p>
                      </div>
                    </a>
                  </li>
                @empty
                  <li class="topbar-dropdown-empty">{{ trans('app.no_data_found') }}</li>
                @endforelse
              </ul>
            </li>
            <li class="footer">
              <a href="{{ url('admin/support/message/labelOf/' . \App\Models\Message::LABEL_INBOX) }}">{{ trans('app.go_to_msg_inbox') }}</a>
            </li>
          </ul>
        </li>

        {{-- Notifications --}}
        <li class="dropdown notifications-menu" id="notifications-dropdown">
          <a href="javascript:void(0)" class="dropdown-toggle topbar-icon-btn" data-toggle="dropdown" title="{{ trans('app.notifications') }}">
            <i class="fa fa-bell-o"></i>
            @if ($count_notification = Auth::user()->unreadNotifications->count())
              <span class="topbar-badge topbar-badge-warning">{{ $count_notification > 9 ? '9+' : $count_notification }}</span>
            @endif
          </a>
          <ul class="dropdown-menu topbar-dropdown">
            <li class="header">{{ trans('messages.notification_count', ['count' => $count_notification]) }}</li>
            <li>
              <ul class="menu topbar-dropdown-list">
                @forelse (Auth::user()->unreadNotifications as $notification)
                  <li>
                    @php
                      $notification_view = 'admin.partials.notifications.' . Str::snake(class_basename($notification->type));
                    @endphp
                    @includeFirst([$notification_view, 'admin.partials.notifications.default'])
                  </li>
                @empty
                  <li class="topbar-dropdown-empty">{{ trans('app.no_data_found') }}</li>
                @endforelse
              </ul>
            </li>
            <li class="footer"><a href="{{ route('admin.notifications') }}">{{ trans('app.view_all_notifications') }}</a></li>
          </ul>
        </li>

        {{-- Announcements --}}
        @if (is_incevio_package_loaded('announcement'))
          @if (Auth::user()->isMerchant() ? ($active_announcements = get_merchant_announcements()) : ($active_announcements = get_all_announcements()))
            <li class="dropdown tasks-menu" id="announcement-dropdown">
              <a href="javascript:void(0)" class="dropdown-toggle topbar-icon-btn" data-toggle="dropdown" title="{{ trans('nav.announcements') }}">
                <i class="fa fa-bullhorn"></i>
              </a>
              <ul class="dropdown-menu topbar-dropdown">
                @foreach ($active_announcements as $active_announcement)
                  <li class="topbar-announcement-item">
                    {!! $active_announcement->parsed_body !!}
                    @if ($active_announcement->action_url)
                      <a href="{{ $active_announcement->action_url }}" class="btn btn-xs btn-new">{{ $active_announcement->action_text }}</a>
                    @endif
                  </li>
                @endforeach
              </ul>
            </li>
          @endif
        @endif

        <li class="topbar-divider hidden-xs"></li>

        {{-- User menu --}}
        <li class="dropdown topbar-user-menu">
          <a href="javascript:void(0)" class="dropdown-toggle topbar-user-trigger" data-toggle="dropdown">
            @if (Auth::user()->image)
              <img src="{{ get_storage_file_url(Auth::user()->image->path, 'tiny') }}" class="user-image" alt="">
            @else
              <img src="{{ get_gravatar_url(Auth::user()->email, 'tiny') }}" class="user-image" alt="">
            @endif
            <span class="topbar-user-info hidden-xs">
              <span class="topbar-user-name">{{ Auth::user()->getName() }}</span>
              <span class="topbar-user-role">
                @if (Auth::user()->isAdmin()) {{ trans('app.admin') }}
                @elseif (Auth::user()->isMerchant()) {{ trans('app.merchant') }}
                @else {{ trans('app.user') }}
                @endif
              </span>
            </span>
            <i class="fa fa-chevron-down topbar-user-chevron hidden-xs"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right topbar-dropdown topbar-user-dropdown">
            <li class="topbar-user-card">
              @if (Auth::user()->image)
                <img src="{{ get_storage_file_url(Auth::user()->image->path, 'tiny') }}" class="topbar-user-card-avatar" alt="">
              @else
                <img src="{{ get_gravatar_url(Auth::user()->email, 'tiny') }}" class="topbar-user-card-avatar" alt="">
              @endif
              <div>
                <strong>{{ Auth::user()->getName() }}</strong>
                <small>{{ Auth::user()->email }}</small>
              </div>
            </li>
            <li><a href="{{ route('admin.account.profile') }}"><i class="fa fa-user fa-fw"></i> {{ trans('app.account') }}</a></li>
            <li class="divider"></li>
            <li class="dropdown-header">{{ trans('app.change_language') }}</li>
            @foreach (config('active_locales') as $lang)
              <li><a href="{{ route('locale.change', $lang->code) }}"><i class="fa fa-globe fa-fw"></i> {{ $lang->language }}</a></li>
            @endforeach
            <li class="divider"></li>
            <li>
              <a href="{{ Request::session()->has('impersonated') ? route('admin.secretLogout') : route('logout') }}" class="topbar-logout-link">
                <i class="fa fa-sign-out fa-fw"></i> {{ trans('app.log_out') }}
              </a>
            </li>
          </ul>
        </li>

      </ul>
    </div>
  </nav>
</header>
