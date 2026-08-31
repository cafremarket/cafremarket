{{-- Single row action button --}}
<a href="{{ $href ?? 'javascript:void(0)' }}"
   class="admin-action-btn {{ !empty($modal) ? 'ajax-modal-btn' : '' }} {{ $class ?? '' }}"
   @if(!empty($modal)) data-link="{{ $href }}" @endif
   title="{{ $title ?? '' }}"
   data-toggle="tooltip"
   data-placement="top">
  <i class="fa {{ $icon }}"></i>
</a>
