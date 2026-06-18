<li class="{{ Request::is('admin/affiliate') && ! Request::is('admin/affiliate/commissions*') ? 'active' : '' }}">
  <a href="{{ route('admin.affiliate.index') }}">
    <i class="fa fa-angle-double-right"></i> {{ trans('packages.affiliate.affiliates') }}
    @include('partials._addon_badge')
  </a>
</li>
<li class="{{ Request::is('admin/affiliate/commissions*') ? 'active' : '' }}">
  <a href="{{ route('admin.affiliate.commissions') }}">
    <i class="fa fa-angle-double-right"></i> {{ trans('packages.affiliate.affiliate_commissions') }}
    @include('partials._addon_badge')
  </a>
</li>
