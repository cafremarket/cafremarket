{{-- Filter toolbar for list pages --}}
<div class="admin-filters">
  @if (!empty($title))
    <span class="admin-filters__label">{{ $title }}</span>
  @endif
  <div class="admin-filters__controls">
    {{ $slot ?? '' }}
  </div>
</div>
