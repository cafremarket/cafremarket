<nav class="mp-page-header" aria-label="Breadcrumb">
  <div class="mp-page-header__inner">
    <div class="mp-page-header__main">
      <div class="mp-breadcrumb">
        <a href="{{ route('merchant.dashboard') }}">
          <i class="fa fa-home"></i> {{ trans('nav.dashboard') }}
        </a>
        @if (View::hasSection('page_title') || isset($page_title))
          <span class="mp-breadcrumb__sep"><i class="fa fa-angle-right"></i></span>
          <span class="mp-breadcrumb__current">
            @hasSection('page_title')
              @yield('page_title')
            @else
              {!! strip_tags($page_title) !!}
            @endif
          </span>
        @endif
      </div>

      @if (View::hasSection('page_title'))
        <h1 class="mp-page-header__title">@yield('page_title')</h1>
      @elseif (isset($page_title))
        <h1 class="mp-page-header__title">{!! $page_title !!}</h1>
      @endif

      @if (View::hasSection('page_description'))
        <p class="mp-page-header__desc">@yield('page_description')</p>
      @elseif (! empty($page_description))
        <p class="mp-page-header__desc">{!! $page_description !!}</p>
      @endif
    </div>

    @hasSection('buttons')
      <div class="mp-page-header__actions">
        @yield('buttons')
      </div>
    @endif
  </div>
</nav>
