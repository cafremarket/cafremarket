@extends('affiliate::backend.master_layout')

@section('page-style')
  @include('plugins.ionic')
@endsection

@section('content')
  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">{{ trans('packages.affiliate.affiliate_links') }}</h3>
    </div> <!-- /.box-header -->
    <div class="box-body responsive-table">
      <table class="table table-hover table-no-sort">
        <thead>
          <tr>
            <th>{{ trans('app.shop') }}</th>
            <th>{{ trans('app.form.url') }}</th>
            <th>{{ trans('app.price') }}</th>
            <th>{{ trans('packages.affiliate.commission_rate') . ' (%)' }}</th>
            <th>{{ trans('theme.total_sold_quantity') }}</th>
            <th>{{ trans('packages.affiliate.visitors') }}</th>
            <th>{{ trans('app.options') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($links as $link)
            @if ($link->inventory)
              <tr>
                <td>{!! $link->inventory->shop->name !!}</td>
                <td>
                  <span class="js-affiliate-link-url">{{ $link->full_url }}</span>

                  <a href="{{ route('show.product', $link->inventory->slug) }}" class="ml-2" target="_blank">
                    <i class="fa fa-external-link text-info" data-toggle="tooltip" data-placement="top" title="{{ trans('packages.affiliate.go_to_product_page') }}"></i>
                  </a>

                  <a href="javascript::void(0)" class="pull-right" onclick="copyAffiliateLink(this)" data-key="copy-affiliate-link">
                    <em class="fa fa-clipboard"></em>
                  </a>
                </td>
                <td>{{ get_formated_currency($link->inventory->sale_price, 2) }}</td>
                <td>{{ $link->inventory->affiliates_percentage }}</td>
                <td>{{ $link->order_count }}</td>
                <td>{{ $link->visitor_count }}</td>
                <td>
                  <a href="javascript:void(0)" data-link="{{ route('affiliate.link.edit', $link->id) }}" data-toggle="tooltip" title={{ trans('app.edit') }} class="ajax-modal-btn">
                    <i class="fa fa-edit"></i>
                  </a>&nbsp;

                  <a href="{{ route('affiliate.link.commissions', $link) }}" data-toggle="tooltip" title="{{ trans('packages.affiliate.affiliate_commissions') }}">
                    <i class="fa fa-percent"></i>
                  </a>&nbsp;

                  {!! Form::open(['route' => ['affiliate.link.destroy', $link->id], 'method' => 'delete', 'class' => 'data-form']) !!}
                  {!! Form::button('<i class="text-muted fa fa-trash"></i>', ['type' => 'submit', 'class' => 'confirm ajax-silent', 'title' => trans('app.delete_permanently'), 'data-toggle' => 'tooltip', 'data-placement' => 'top']) !!}
                  {!! Form::close() !!}
                </td>
              </tr>
            @endif
          @endforeach
        </tbody>
      </table>
    </div>
  </div> <!-- /.box -->

  <div class="box collapsed-box">
    <div class="box-header with-border">
      <div class="box-title">
        {{ trans('packages.affiliate.invalid_links') }}
        <small> ({{ trans('packages.affiliate.item_not_available') }})</small>
      </div>
      <div class="box-tools pull-right">
        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>
        <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-remove"></i></button>
      </div>
    </div>
    <div class="box-body">
      <table class="table table-hover table-no-sort">
        <thead>
          <tr>
            <th>{{ trans('app.slug') }}</th>
            <th class="text-center">{{ trans('theme.total_sold_quantity') }}</th>
            <th class="text-center">{{ trans('packages.affiliate.visitors') }}</th>
            <th class="text-center">{{ trans('app.options') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($links as $link)
            @unless ($link->inventory)
              <tr>
                <td>{{ $link->slug }}</td>
                <td class="text-center">{{ $link->order_count }}</td>
                <td class="text-center">{{ $link->visitor_count }}</td>
                <td class="text-center">
                  {!! Form::open(['route' => ['affiliate.link.destroy', $link->id], 'method' => 'delete', 'class' => 'data-form']) !!}
                  {!! Form::button('<i class="text-muted fa fa-trash"></i>', ['type' => 'submit', 'class' => 'confirm ajax-silent', 'title' => trans('app.delete_permanently'), 'data-toggle' => 'tooltip', 'data-placement' => 'top']) !!}
                  {!! Form::close() !!}
                </td>
              </tr>
            @endunless
          @endforeach
        </tbody>
      </table>
    </div>
  </div> <!-- /.box -->
@endsection
