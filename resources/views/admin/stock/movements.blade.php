@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.stock_movements') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.stock_movements'),
    'icon' => 'fa-exchange',
  ])

  <form method="get" class="form-inline admin-filter-bar" style="margin-bottom:15px;">
    <select name="type" class="form-control">
      <option value="">{{ trans('app.all') }} {{ trans('app.type') }}</option>
      @foreach (['in','out','adjust','transfer_out','transfer_in','sale','refund','damage','reserve','release'] as $type)
        <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ trans('app.stock_type_'.$type) }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn btn-default">{{ trans('app.filter') }}</button>
  </form>

  <table class="table table-hover admin-table">
    <thead>
      <tr>
        <th>{{ trans('app.date') }}</th>
        <th>{{ trans('app.inventory') }}</th>
        <th>{{ trans('app.warehouse') }}</th>
        <th>{{ trans('app.type') }}</th>
        <th>{{ trans('app.quantity') }}</th>
        <th>{{ trans('app.before') }}</th>
        <th>{{ trans('app.after') }}</th>
        <th>{{ trans('app.user') }}</th>
        <th>{{ trans('app.notes') }}</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($movements as $movement)
        <tr>
          <td>{{ $movement->created_at }}</td>
          <td>
            {{ optional($movement->inventory)->title }}
            <br><small class="text-muted">{{ optional($movement->inventory)->sku }}</small>
          </td>
          <td>{{ optional($movement->warehouse)->name }}</td>
          <td><span class="label label-default">{{ trans('app.stock_type_'.$movement->type) }}</span></td>
          <td>{{ $movement->quantity }}</td>
          <td>{{ $movement->quantity_before }}</td>
          <td>{{ $movement->quantity_after }}</td>
          <td>{{ optional($movement->user)->name }}</td>
          <td>{{ $movement->notes }}</td>
        </tr>
      @empty
        <tr><td colspan="9" class="text-center">{{ trans('app.no_data_found') }}</td></tr>
      @endforelse
    </tbody>
  </table>

  {{ $movements->links() }}

  @include('admin.partials.ui.card_end')
@endsection
