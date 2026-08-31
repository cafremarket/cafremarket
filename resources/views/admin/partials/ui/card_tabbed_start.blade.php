{{-- Tabbed admin card. Pass: title, icon (optional), actions (optional HTML) --}}
<div class="box admin-card admin-card--tabbed {{ $class ?? '' }}">
  @if (!empty($title))
    <div class="box-header with-border admin-card__header">
      <div class="admin-card__header-main">
        <h3 class="box-title admin-card__title">
          @if (!empty($icon))
            <span class="admin-card__icon-wrap"><i class="fa {{ $icon }}"></i></span>
          @endif
          {{ $title }}
        </h3>
      </div>
      @if (!empty($actions))
        <div class="box-tools pull-right admin-card__actions">
          {!! $actions !!}
        </div>
      @endif
    </div>
  @endif
  <div class="nav-tabs-custom">
