<div class="sf-account-settings">
  <section class="sf-account-section">
    <div class="sf-account-section__head">
      <span class="sf-account-section__icon"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></span>
      <div>
        <h2 class="sf-account-section__title">@lang('theme.addresses')</h2>
        <p class="sf-account-section__hint">@lang('theme.set_delivery_location')</p>
      </div>
    </div>

    <div class="sf-form-panel">
      <div class="sf-address-toolbar">
        <div></div>
        <a href="{{ route('my.address.create') }}" class="modalAction btn sf-btn-primary">
          <i class="fas fa-plus" aria-hidden="true"></i> @lang('theme.button.add_new_address')
        </a>
      </div>

      <div class="sf-address-grid">
        @forelse($addresses->addresses as $address)
          <article class="sf-address-card" id="address-{{ $address->id }}">
            <div class="sf-address-card__head">
              <span class="sf-address-card__type">{{ $address->address_type }}</span>
              <div class="sf-address-card__actions">
                <a href="{{ route('my.address.edit', $address) }}" class="modalAction btn btn-default btn-xs" title="@lang('theme.edit')">
                  <i class="fas fa-edit" aria-hidden="true"></i>
                </a>
                <a href="{{ route('my.address.delete', $address->id) }}" class="confirm btn btn-default btn-xs" data-confirm="@lang('theme.confirm_action.delete')" title="@lang('theme.button.delete')">
                  <i class="fas fa-trash" aria-hidden="true"></i>
                </a>
              </div>
            </div>
            <div class="sf-address-card__body">
              {!! $address->toHtml() !!}
            </div>
          </article>
        @empty
          <div class="sf-empty-state">
            <i class="fas fa-map-marked-alt" aria-hidden="true"></i>
            <p>@lang('theme.nothing_found')</p>
            <a href="{{ route('my.address.create') }}" class="modalAction btn sf-btn-primary">
              @lang('theme.button.add_new_address')
            </a>
          </div>
        @endforelse
      </div>
    </div>
  </section>
</div>

@if (request()->has('address'))
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var section = document.getElementById('address-{{ request('address') }}');
      if (section) {
        section.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  </script>
@endif
