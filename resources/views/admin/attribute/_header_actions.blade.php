@can('create', \App\Models\AttributeValue::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.catalog.attributeValue.create') }}" class="ajax-modal-btn btn btn-default btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_attribute_value') }}
  </a>
@endcan
@can('create', \App\Models\Attribute::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.catalog.attribute.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_attribute') }}
  </a>
@endcan
