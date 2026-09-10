@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.stock_transfers') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.stock_transfers'),
    'icon' => 'fa-random',
    'actions' => view('admin.stock._transfer_header_actions')->render(),
  ])

  <table class="table table-hover admin-table">
    <thead>
      <tr>
        <th>{{ trans('app.date') }}</th>
        <th>{{ trans('app.from') }}</th>
        <th>{{ trans('app.to') }}</th>
        <th>{{ trans('app.items') }}</th>
        <th>{{ trans('app.status') }}</th>
        <th>{{ trans('app.user') }}</th>
        <th>{{ trans('app.notes') }}</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($transfers as $transfer)
        <tr>
          <td>{{ $transfer->completed_at ?? $transfer->created_at }}</td>
          <td>{{ optional($transfer->fromWarehouse)->name }}</td>
          <td>{{ optional($transfer->toWarehouse)->name }}</td>
          <td>
            @foreach ($transfer->items as $item)
              <div>{{ optional($item->inventory)->sku }} × {{ $item->quantity }}</div>
            @endforeach
          </td>
          <td>{{ $transfer->status }}</td>
          <td>{{ optional($transfer->user)->name }}</td>
          <td>{{ $transfer->notes }}</td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-center">{{ trans('app.no_data_found') }}</td></tr>
      @endforelse
    </tbody>
  </table>

  {{ $transfers->links() }}

  @include('admin.partials.ui.card_end')
@endsection
