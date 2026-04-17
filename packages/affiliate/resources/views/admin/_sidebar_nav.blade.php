<li class="{{ Request::is('admin/affiliate') ? 'active' : '' }}">
  <a href="{{ route('admin.affiliate.index') }}">
    <i class="fa fa-angle-double-right"></i> {{ trans('packages.affiliate.affiliates') }}
    @include('partials._addon_badge')
  </a>
</li>
