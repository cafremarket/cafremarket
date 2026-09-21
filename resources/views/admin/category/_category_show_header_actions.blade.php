@can('update', $category)
  <a href="javascript:void(0)" data-link="{{ route('admin.catalog.category.edit', $category->id) }}" class="ajax-modal-btn btn btn-default btn-flat">
    <i class="fa fa-edit"></i> {{ trans('app.edit') }}
  </a>
@endcan
@can('create', \App\Models\SubCategory::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.catalog.subcategory.translate.bulk') }}" class="ajax-modal-btn btn btn-default btn-flat">
    <i class="fa fa-language"></i> {{ trans('app.bulk_translation_import') }}
  </a>
  <a href="javascript:void(0)" data-link="{{ route('admin.catalog.subcategory.create', ['category_id' => $category->id]) }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_subcategory') }}
  </a>
@endcan
