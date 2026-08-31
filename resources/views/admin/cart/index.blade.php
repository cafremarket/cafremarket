@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.cart_list') }}
@endsection

@section('content')
  @php
    $cartModel = \App\Models\Cart::class;
    $massActions = [
      ['url' => route('admin.order.cart.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.order.cart.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @can('index', $cartModel)
    @include('admin.partials.ui.card_start', [
      'title' => trans('app.cart_list'),
      'icon' => 'fa-shopping-cart',
      'actions' => view('admin.cart._header_actions')->render(),
    ])

    <table class="table table-hover admin-table table-no-option">
      <thead>
        <tr>
          @include('admin.partials.ui.mass_checkbox_header', ['model' => $cartModel, 'massActions' => $massActions])
          @cannot('massDelete', $cartModel)
            {{-- no mass column --}}
          @endcannot
          <th>{{ trans('app.created_at') }}</th>
          <th>{{ trans('app.customer') }}</th>
          <th>{{ trans('app.items') }}</th>
          <th>{{ trans('app.quantities') }}</th>
          <th>{{ trans('app.grand_total') }}</th>
          <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
        </tr>
      </thead>
      <tbody id="massSelectArea">
        @foreach ($cart_lists as $cart_list)
          <tr>
            @can('massDelete', $cartModel)
              <td><input id="{{ $cart_list->id }}" type="checkbox" class="massCheck"></td>
            @endcan
            <td>{{ $cart_list->created_at->diffForHumans() }}</td>
            <td>{{ $cart_list->customer->name }}</td>
            <td>{{ $cart_list->item_count }}</td>
            <td>{{ $cart_list->quantity }}</td>
            <td>{{ get_formated_currency($cart_list->grand_total, 2, config('system_settings.currency.id')) }}</td>
            <td class="row-options admin-row-actions">
              @if (Gate::allows('create', \App\Models\Order::class) || Gate::allows('update', $cart_list))
                {!! Form::open(['route' => ['admin.order.order.create'], 'method' => 'get', 'class' => 'admin-inline-form']) !!}
                {{ Form::hidden('customer_id', $cart_list->customer->id) }}
                {{ Form::hidden('cart_id', $cart_list->id) }}
                <button type="submit" class="admin-action-btn" title="{{ trans('app.use_this_cart') }}" data-toggle="tooltip"><i class="fa fa-check"></i></button>
                {!! Form::close() !!}
              @endif
              @can('view', $cart_list)
                <a href="javascript:void(0)" data-link="{{ route('admin.order.cart.show', $cart_list->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.detail') }}" data-toggle="tooltip"><i class="fa fa-expand"></i></a>
              @endcan
              @can('delete', $cart_list)
                {!! Form::open(['route' => ['admin.order.cart.trash', $cart_list->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
                <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.trash') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
                {!! Form::close() !!}
              @endcan
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

    @include('admin.partials.ui.card_end')
  @endcan

  @include('admin.partials.ui.trash_start', ['title' => trans('app.trash')])

  <table class="table table-hover admin-table table-2nd-sort">
    <thead>
      <tr>
        <th>{{ trans('app.created_at') }}</th>
        <th>{{ trans('app.customer') }}</th>
        <th>{{ trans('app.items') }}</th>
        <th>{{ trans('app.quantities') }}</th>
        <th>{{ trans('app.grand_total') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td>{{ $trash->created_at->diffForHumans() }}</td>
          <td>{{ $trash->customer->getName() }}</td>
          <td>{{ $trash->item_count }}</td>
          <td>{{ $trash->quantity }}</td>
          <td>{{ get_formated_currency($trash->grand_total, 2, config('system_settings.currency.id')) }}</td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.order.cart.restore', $trash->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.order.cart.destroy', $trash->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete_permanently') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
