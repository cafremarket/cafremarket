{{-- Selectable customer address cards for the "Create custom order" modal --}}
<div class="mpc-addr-list">
  @foreach ($addresses as $address)
    @php
      $cityLine = collect([$address['city'], $address['state'], $address['zip_code']])->filter()->implode(', ');
      $title = $address['address_title'] ?: $address['address_type'];
    @endphp
    <label class="mpc-addr-card">
      <input type="radio" name="{{ $name }}" value="{{ $address['id'] }}" {{ (int) $selected === (int) $address['id'] ? 'checked' : '' }}>
      <span class="mpc-addr-card__body">
        <span class="mpc-addr-card__title">
          {{ $title }}
          @if ($address['address_type'] && $address['address_type'] !== $title)
            <span class="mpc-addr-card__type">{{ strtoupper($address['address_type']) }}</span>
          @endif
        </span>
        @foreach (array_filter([$address['address_line_1'], $address['address_line_2'], $address['landmark'], $cityLine, $address['country']]) as $line)
          <span class="mpc-addr-card__line">{{ $line }}</span>
        @endforeach
        @if ($address['phone'])
          <span class="mpc-addr-card__line">P: {{ $address['phone'] }}</span>
        @endif
        @if ($address['shipping_zone'])
          <span class="mpc-addr-card__zone">Shipping zone: {{ $address['shipping_zone']['name'] }}</span>
        @else
          <span class="mpc-addr-card__zone mpc-addr-card__zone--none">No shipping zone covers this address</span>
        @endif
      </span>
    </label>
  @endforeach
</div>
