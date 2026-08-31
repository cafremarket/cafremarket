{!! Form::open(['route' => 'admin.utility.emailLog.clear', 'method' => 'delete', 'class' => 'admin-inline-form confirm']) !!}
  <button type="submit" class="btn btn-danger btn-flat btn-sm">
    <i class="fa fa-trash"></i> {{ trans('app.empty_trash') }}
  </button>
{!! Form::close() !!}
