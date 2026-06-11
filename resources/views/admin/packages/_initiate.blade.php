<div class="modal-dialog modal-md">
  <div class="modal-content">
    {!! Form::open(['route' => ['admin.package.install', $installable['slug']], 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.install') }}
    </div>
    <div class="modal-body">
      <p>{{ trans('app.install') }}: <strong>{{ $installable['name'] ?? $installable['slug'] }}</strong></p>
    </div>
    <div class="modal-footer">
      {!! Form::submit(trans('app.install'), ['class' => 'btn btn-flat btn-lg btn-primary']) !!}
    </div>
    {!! Form::close() !!}
  </div>
</div>
