@extends('admin.layouts.master')

@section('page_title')
  {{ trans('packages.affiliate.affiliate_links') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('packages.affiliate.affiliate_links'),
    'icon' => 'fa-link',
    'bodyClass' => 'responsive-table',
  ])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.shop') }}</th>
        <th>{{ trans('app.form.url') }}</th>
        <th>{{ trans('app.price') }}</th>
        <th>{{ trans('packages.affiliate.commission_rate') . ' (%)' }}</th>
        <th>{{ trans('theme.total_sold_quantity') }}</th>
        <th>{{ trans('packages.affiliate.visitors') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.options') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($links as $link)
        @if ($link->inventory)
          <tr>
            <td>{!! $link->inventory->shop->name !!}</td>
            <td>
              <span class="js-affiliate-link-url">{{ $link->full_url }}</span>
              <a href="{{ storefront_product_url($link->inventory) }}" target="_blank" class="admin-action-btn" title="{{ trans('packages.affiliate.go_to_product_page') }}" data-toggle="tooltip"><i class="fa fa-external-link"></i></a>
              <a href="javascript:void(0)" class="admin-action-btn" onclick="copyAffiliateLink(this)" data-key="copy-affiliate-link" title="{{ trans('app.copy') }}" data-toggle="tooltip"><i class="fa fa-clipboard"></i></a>
            </td>
            <td>{{ get_formated_currency($link->inventory->sale_price, 2) }}</td>
            <td>{{ $link->inventory->affiliates_percentage }}</td>
            <td>{{ $link->order_count }}</td>
            <td>{{ $link->visitor_count }}</td>
            <td class="row-options admin-row-actions">
              <a href="javascript:void(0)" data-link="{{ route('affiliate.link.edit', $link->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
              {!! Form::open(['route' => ['affiliate.link.destroy', $link->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete_permanently') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
            </td>
          </tr>
        @endif
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.trash_start', ['title' => trans('app.trash')])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.slug') }}</th>
        <th>{{ trans('theme.total_sold_quantity') }}</th>
        <th>{{ trans('packages.affiliate.visitors') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.options') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($links as $link)
        @unless ($link->inventory)
          <tr>
            <td>{{ $link->slug }}</td>
            <td>{{ $link->order_count }}</td>
            <td>{{ $link->visitor_count }}</td>
            <td class="row-options admin-row-actions">
              {!! Form::open(['route' => ['affiliate.link.destroy', $link->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete_permanently') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
            </td>
          </tr>
        @endunless
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
