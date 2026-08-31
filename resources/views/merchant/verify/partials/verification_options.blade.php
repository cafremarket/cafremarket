@php
  $shop = $config->shop;
  $shopPhone = $config->support_phone ?: optional(Auth::user())->phone;
  $hasPhone = filled(trim((string) $shopPhone));
  $options = [
    [
      'id' => 'person',
      'anchor' => 'mp-verify-person',
      'icon' => 'fa-user',
      'title' => trans('app.person_verified'),
      'description' => trans('messages.verification_option_person_help'),
      'seller_done' => $hasDocuments,
      'admin_done' => $shop->id_verified,
      'editable' => $editable ?? false,
    ],
    [
      'id' => 'phone',
      'anchor' => 'mp-verify-phone',
      'icon' => 'fa-phone',
      'title' => trans('app.phone_verified'),
      'description' => trans('messages.verification_option_phone_help'),
      'seller_done' => $hasPhone,
      'admin_done' => $shop->phone_verified,
      'editable' => $editable ?? false,
    ],
    [
      'id' => 'address',
      'anchor' => 'mp-verify-address',
      'icon' => 'fa-map-marker',
      'title' => trans('app.address_verified'),
      'description' => trans('messages.verification_option_address_help'),
      'seller_done' => $hasLocation,
      'admin_done' => $shop->address_verified,
      'editable' => $editable ?? false,
    ],
  ];
@endphp

<div class="mp-panel mp-panel--options">
  <div class="mp-panel__head">
    <div class="mp-panel__head-icon"><i class="fa fa-check-square-o"></i></div>
    <div class="mp-panel__head-text">
      <h2>{{ trans('app.verification_options') }}</h2>
      <p>{{ trans('messages.verification_options_intro') }}</p>
    </div>
  </div>
  <div class="mp-panel__body">
    <div class="mp-verify-options__grid">
      @foreach ($options as $option)
        <div class="mp-verify-option {{ $option['seller_done'] ? 'is-seller-done' : 'is-pending' }} {{ $option['admin_done'] ? 'is-admin-done' : '' }}">
          <div class="mp-verify-option__top">
            <div class="mp-verify-option__icon"><i class="fa {{ $option['icon'] }}"></i></div>
            <div class="mp-verify-option__body">
              <div class="mp-verify-option__title-row">
                <h3>{{ $option['title'] }}</h3>
                @if ($option['admin_done'])
                  <span class="mp-verify-option__badge mp-verify-option__badge--success">{{ trans('app.verified') }}</span>
                @elseif ($option['seller_done'])
                  <span class="mp-verify-option__badge mp-verify-option__badge--info">{{ trans('app.submitted') }}</span>
                @else
                  <span class="mp-verify-option__badge mp-verify-option__badge--warning">{{ trans('app.action_required') }}</span>
                @endif
              </div>
              <p>{{ $option['description'] }}</p>
            </div>
          </div>
          <ul class="mp-verify-option__steps">
            <li class="{{ $option['seller_done'] ? 'is-done' : '' }}">
              <i class="fa fa-{{ $option['seller_done'] ? 'check' : 'circle-o' }}"></i>
              {{ trans('messages.verification_option_your_part') }}
            </li>
            <li class="{{ $option['admin_done'] ? 'is-done' : '' }}">
              <i class="fa fa-{{ $option['admin_done'] ? 'check' : 'circle-o' }}"></i>
              {{ trans('messages.verification_option_admin_part') }}
            </li>
          </ul>
          @if (($option['editable'] ?? false) && ! $option['seller_done'])
            <a href="#{{ $option['anchor'] }}" class="mp-btn mp-btn--secondary mp-verify-option__action">
              {{ trans('app.complete_now') }}
            </a>
          @elseif ($option['editable'] ?? false)
            <a href="#{{ $option['anchor'] }}" class="mp-verify-option__link">{{ trans('app.edit') }}</a>
          @endif
        </div>
      @endforeach
    </div>
  </div>
</div>
