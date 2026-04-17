<!-- Main Header -->
<header class="main-header">
    <!-- Logo -->
    <a href="{{ url('/') }}" class="logo">
      <!-- mini logo for sidebar mini 50x50 pixels -->
      <span class="logo-mini">{{ Str::limit(get_site_title(), 2, '.') }}</span>
  
      <!-- logo for regular state and mobile devices -->
      <span class="logo-lg">{{ get_site_title() }}</span>
    </a>
  
    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation">
      <!-- Sidebar toggle button-->
      <a href="javascript::void(0)" class="sidebar-toggle" data-toggle="offcanvas" role="button">
        <span class="sr-only">{{ trans('packages.affiliate.toggle_navigation') }}</span>
      </a>
  
      <ul class="nav navbar-nav hidden-xs">
        <li class="user user-menu">
          <a href="{{ route('affiliate.profile') }}">
            @if (auth()->guard('affiliate')->user()->image)
              <img src="{{ get_storage_file_url(auth()->guard('affiliate')->user()->image->path, 'tiny') }}" class="user-image" alt="{{ trans('app.avatar') }}">
            @else
              <img src="{{ get_gravatar_url(auth()->guard('affiliate')->user()->email, 'tiny') }}" class="user-image" alt="{{ trans('app.avatar') }}">
            @endif
            <!-- hidden-xs hides the username on small devices so only the image appears. -->
            <span class="hidden-xs"> {{ trans('app.welcome') . ' ' . auth()->guard('affiliate')->user()->getName() }}</span>
          </a>
        </li>
  
        <li>
          <a href="{{ get_shop_url() }}" target="_blank">
            <i class="fa fa-external-link mr-2"></i> {{ trans('app.store_front') }}
          </a>
        </li>
      </ul>
  
      <!-- Navbar Right Menu -->
      <div class="navbar-custom-menu">
        <ul class="nav navbar-nav">

          <!-- Announcement Menu -->
          @if (is_incevio_package_loaded('announcement'))
            @if (auth()->guard('affiliate')->user()->isMerchant() ? ($active_announcements = get_merchant_announcements()) : ($active_announcements = get_all_announcements()))
              <li class="dropdown tasks-menu" id="announcement-dropdown">
                <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown">
                  <i class="fa fa-bullhorn"></i>
                  {{--                @if ($active_announcement && $active_announcement->updated_at > auth()->guard('affiliate')->user()->read_announcements_at) --}}
                  {{--                  <span class="label"><i class="fa fa-circle"></i></span> --}}
                  {{--                @endif --}}
                </a>
                <ul class="dropdown-menu">
                  @foreach ($active_announcements as $active_announcement)
                    <li>
                      {!! $active_announcement->parsed_body !!}
                      @if ($active_announcement->action_url)
                        <span class="indent10">
                          <a href="{{ $active_announcement->action_url }}" class="btn btn-flat btn-default btn-xs">{{ $active_announcement->action_text }}</a>
                        </span>
                      @endif
                    </li>
                  @endforeach
                </ul>
              </li> <!-- /.notifications-menu -->
            @endif
          @endif
    
          <li>
            <a href="{{ Request::session()->has('impersonated') ? route('admin.secretLogout') : route('affiliate.logout') }}"><i class="fa fa-sign-out"></i> <span class="hidden-xs">{{ trans('app.log_out') }}</span></a>
          </li>
  
          <li>
            {{-- <a href="javascript:void(0)" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a> --}}
          </li>
        </ul>
      </div>
    </nav>
  </header>
  