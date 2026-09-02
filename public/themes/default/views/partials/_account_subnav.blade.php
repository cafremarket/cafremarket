@php
  $accountTabs = ['account', 'password', 'addresses', 'account_delete'];
@endphp

@if (in_array($tab, $accountTabs))
  <ul class="sf-account-tabs" role="navigation" aria-label="@lang('theme.nav.my_account')">
    <li class="{{ $tab === 'account' ? 'active' : '' }}">
      <a href="{{ route('account', 'account') }}">
        <i class="fas fa-user" aria-hidden="true"></i>
        @lang('theme.basic_info')
      </a>
    </li>
    <li class="{{ $tab === 'password' ? 'active' : '' }}">
      <a href="{{ route('account.password') }}">
        <i class="fas fa-lock" aria-hidden="true"></i>
        @lang('theme.change_password')
      </a>
    </li>
    <li class="{{ $tab === 'addresses' ? 'active' : '' }}">
      <a href="{{ route('account.addresses') }}">
        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
        @lang('theme.addresses')
      </a>
    </li>
    <li class="{{ $tab === 'account_delete' ? 'active' : '' }}">
      <a href="{{ route('account.delete') }}">
        <i class="fas fa-trash-alt" aria-hidden="true"></i>
        @lang('theme.button.delete')
      </a>
    </li>
  </ul>
@endif
