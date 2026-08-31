{!! Form::open(['route' => ['admin.notifications.deleteAll'], 'method' => 'delete', 'class' => 'admin-inline-form confirm']) !!}
  <button type="submit" class="btn btn-flat btn-new">
    <i class="fa fa-trash-o"></i> {{ trans('app.delete_all') }}
  </button>
{!! Form::close() !!}
