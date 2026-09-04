@if ($gift_cards->count() > 0)
  <div class="sf-coupon-grid">
    @foreach ($gift_cards as $gift)
      @php
        $expired = $gift->expiry_time && $gift->expiry_time < \Carbon\Carbon::now();
      @endphp

      <article class="sf-gift-card {{ $expired ? 'sf-gift-card--expired' : '' }}">
        <div class="sf-gift-card__value">
          <i class="fas fa-gift" aria-hidden="true"></i> {{ get_formated_currency($gift->value) }}
        </div>
        <div class="sf-gift-card__serial">{{ $gift->serial_number }}</div>
        <div class="sf-gift-card__meta mb-2">
          @lang('theme.support_partial_use'):
          @if ($gift->partial_use)
            <span class="label label-outline">@lang('theme.yes')</span>
          @else
            <span class="label label-default">@lang('theme.no')</span>
          @endif
        </div>
        <div class="sf-gift-card__meta">
          @if ($gift->expiry_time)
            @if ($gift->expiry_time && $gift->expiry_time < \Carbon\Carbon::now())
              {{ trans('theme.expired_at') }}: {{ $gift->expiry_time->format('M j,y g:i a') }}
            @elseif($gift->activation_time < \Carbon\Carbon::now())
              {{ trans('theme.use_before') }}: {{ $gift->expiry_time->format('M j,y g:i a') }}
            @elseif($gift->activation_time > \Carbon\Carbon::now())
              {{ trans('theme.use_between') }}:
              {{ $gift->activation_time->format('M j,y g:i a') }}
              @lang('theme.and')
              {{ $gift->expiry_time->format('M j,y g:i a') }}
            @else
              {{ trans('theme.invalid') }}
            @endif
          @elseif($gift->activation_time > \Carbon\Carbon::now())
            {{ trans('theme.valid_from') }}: {{ $gift->activation_time->format('M j,y g:i a') }}
          @else
            {{ trans('theme.lifetime') }}
          @endif
        </div>
      </article>
    @endforeach
  </div>
@else
  <div class="sf-empty-state">
    <i class="fas fa-gift" aria-hidden="true"></i>
    <p>@lang('theme.nothing_found')</p>
  </div>
@endif

<div class="row pagenav-wrapper mb-3">
  {{ $gift_cards->links('theme::layouts.pagination') }}
</div>
