@can('create', \App\Models\Category::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.catalog.category.translate.bulk') }}" class="ajax-modal-btn btn btn-default btn-flat">
    <em class="fa fa-language"></em> {{ trans('app.bulk_translation_import') }}
  </a>
  <a href="javascript:void(0)" data-link="{{ route('admin.catalog.category.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_category') }}
  </a>
@endcan
