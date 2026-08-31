@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.platform_riders') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.platform_riders'),
    'icon' => 'fa-motorcycle',
    'actions' => '<a href="' . route('admin.admin.platform_rider.create') . '" class="btn btn-default btn-xs btn-flat"><i class="fa fa-plus"></i> ' . e(trans('app.add_platform_rider')) . '</a>',
  ])
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>{{ trans('app.name') }}</th>
            <th>{{ trans('app.email') }}</th>
            <th>{{ trans('app.phone') }}</th>
            <th>{{ trans('app.status') }}</th>
            <th>{{ trans('app.online') }}</th>
            <th>{{ trans('app.options') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($deliveryBoys as $rider)
            <tr>
              <td>{{ $rider->getName() }}</td>
              <td>{{ $rider->email }}</td>
              <td>{{ $rider->phone_number }}</td>
              <td>{!! $rider->status ? '<span class="label label-success">'.trans('app.active').'</span>' : '<span class="label label-default">'.trans('app.inactive').'</span>' !!}</td>
              <td>{!! $rider->is_online ? '<span class="label label-info">'.trans('app.online').'</span>' : '<span class="label label-default">'.trans('app.offline').'</span>' !!}</td>
              <td>
                <a href="{{ route('admin.admin.platform_rider.edit', $rider) }}" class="btn btn-xs btn-default">{{ trans('app.edit') }}</a>
                {!! Form::open(['route' => ['admin.admin.platform_rider.trash', $rider], 'method' => 'delete', 'class' => 'inline']) !!}
                  <button type="submit" class="btn btn-xs btn-danger">{{ trans('app.trash') }}</button>
                {!! Form::close() !!}
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-muted">{{ trans('app.no_records_found') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  @include('admin.partials.ui.card_end')
@endsection
