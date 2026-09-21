@can('update', $subCategory)
  <a href="javascript:void(0)" data-link="{{ route('admin.catalog.subcategory.edit', $subCategory->id) }}" class="ajax-modal-btn btn btn-default btn-flat">
    <i class="fa fa-edit"></i> {{ trans('app.edit') }}
  </a>
@endcan
