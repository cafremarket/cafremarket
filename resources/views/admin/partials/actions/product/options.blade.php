@php
  // These rows are injected by the DataTables AJAX call, after the page's
  // one-time admin->merchant link rewrite already ran — so routes must be
  // resolved with the right panel prefix here, not patched client-side.
  $routePrefix = Auth::user()->isFromMerchant() ? 'merchant.catalog.product.' : 'admin.catalog.product.';
@endphp

@can('view', $product)
  <a href="javascript:void(0)" data-link="{{ route($routePrefix.'show', $product->id) }}" class="ajax-modal-btn"><i data-toggle="tooltip" data-placement="top" title="{{ trans('app.detail') }}" class="fa fa-expand"></i></a>&nbsp;
@endcan

@can('update', $product)
  <a href="{{ route($routePrefix.'edit', $product->id) }}"><i data-toggle="tooltip" data-placement="top" title="{{ trans('app.edit') }}" class="fa fa-edit"></i></a>&nbsp;
  <a href="{{ route($routePrefix.'translate.form', ['product' => $product, 'language' => config('system_settings.default_language')]) }}"><em data-toggle="tooltip" data-placement="top" title="{{ trans('app.manage_translations') }}" class="fa fa-language"></em></a>&nbsp;
@endcan

@can('delete', $product)
  {!! Form::open(['route' => [$routePrefix.'trash', $product->id], 'method' => 'delete', 'class' => 'data-form']) !!}
  {!! Form::button('<i class="fa fa-trash-o"></i>', ['type' => 'submit', 'class' => 'confirm ajax-silent', 'title' => trans('app.trash'), 'data-toggle' => 'tooltip', 'data-placement' => 'top']) !!}
  {!! Form::close() !!}
@endcan
