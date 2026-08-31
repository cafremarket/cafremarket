<section class="content-header admin-page-header">
  <div class="admin-page-header__inner">
    <div class="admin-page-header__main">
      <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ url('admin/dashboard') }}">
          <i class="fa fa-home"></i>
          <span>{{ trans('nav.dashboard') }}</span>
        </a>
        @if (View::hasSection('page_title') || isset($page_title))
          <span class="admin-breadcrumb__sep"><i class="fa fa-angle-right"></i></span>
          <span class="admin-breadcrumb__current">
            @hasSection('page_title')
              @yield('page_title')
            @else
              {!! strip_tags($page_title) !!}
            @endif
          </span>
        @endif
      </nav>

      @if (View::hasSection('page_title'))
        <h1 class="admin-page-header__title">@yield('page_title')</h1>
      @elseif (isset($page_title))
        <h1 class="admin-page-header__title">{!! $page_title !!}</h1>
      @endif

      @if (View::hasSection('page_description'))
        <p class="admin-page-header__desc">@yield('page_description')</p>
      @elseif (!empty($page_description))
        <p class="admin-page-header__desc">{!! $page_description !!}</p>
      @endif
    </div>

    @hasSection('buttons')
      <div class="admin-page-header__actions">
        @yield('buttons')
      </div>
    @endif
  </div>
</section>
