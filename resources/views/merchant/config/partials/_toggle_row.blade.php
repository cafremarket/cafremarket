@php
  $toggleRoute = panel_route_name('admin.setting.config.notification.toggle');
  $can_update = $can_update ?? (Gate::allows('update', $config ?? null) ?? null);
@endphp

<div class="row">
  <div class="col-sm-8 text-right">
    <div class="form-group">
      {!! Form::label($field, $label . ':', ['class' => 'with-help control-label']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ $help }}"></i>
    </div>
  </div>
  <div class="col-sm-4">
    @if ($can_update)
      <div class="handle horizontal">
        <a href="javascript:void(0)" data-link="{{ route($toggleRoute, $field) }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $active ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $active ? 'true' : 'false' }}" autocomplete="off">
          <div class="btn-handle"></div>
        </a>
      </div>
    @else
      <span>{{ $active ? trans('app.on') : trans('app.off') }}</span>
    @endif
  </div>
</div>
