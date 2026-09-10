<div class="row">
  <div class="col-sm-7 text-right">
    <div class="form-group">
      {!! Form::label($field, $label . ':', ['class' => 'with-help control-label']) !!}
      @if (!empty($help))
        <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ $help }}"></i>
      @endif
    </div>
  </div>
  <div class="col-sm-4">
    @if ($can_update)
      <div class="handle horizontal text-center">
        <a href="javascript:void(0)"
           data-link="{{ route('admin.setting.system.config.toggle', $field) }}"
           type="button"
           class="btn btn-md btn-secondary btn-toggle {{ $active ? 'active' : '' }}"
           data-toggle="button"
           aria-pressed="{{ $active ? 'true' : 'false' }}"
           @if (!empty($reload)) data-doafter="reload" @endif
           autocomplete="off">
          <div class="btn-handle"></div>
        </a>
      </div>
    @else
      <span>{{ $active ? trans('app.on') : trans('app.off') }}</span>
    @endif
  </div>
</div>
