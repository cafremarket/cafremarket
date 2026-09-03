 <a href="{{ storefront_product_url($inventory) }}" target="_blank"><i data-toggle="tooltip" data-placement="top" title="{{ trans('app.view_as_customer') }}" class="fa fa-external-link"></i></a>&nbsp;
 
 @can('update', $inventory->product)
     <a href="{{ mp_route('admin.stock.product.edit', $inventory->product->id) }}"><i data-toggle="tooltip" data-placement="top" title="{{ trans('app.edit') }}" class="fa fa-edit"></i></a>&nbsp;
 @endcan

 @can('update', $inventory)
   <a href={{ route('admin.stock.inventory.translation.form', ['inventory' => $inventory, 'language' => config('system_settings.default_language')]) }}><i data-toggle="tooltip" data-placement="top" title="{{ trans('app.manage_translations') }}" class="fa fa-language"></i></a>&nbsp;
 @endcan

 @can('delete', $inventory)
   {!! Form::open(['route' => ['admin.stock.inventory.trash', $inventory->id], 'method' => 'delete', 'class' => 'data-form']) !!}
   {!! Form::button('<i class="fa fa-trash-o"></i>', ['type' => 'submit', 'class' => 'confirm ajax-silent', 'title' => trans('app.trash'), 'data-toggle' => 'tooltip', 'data-placement' => 'top']) !!}
   {!! Form::close() !!}
 @endcan
