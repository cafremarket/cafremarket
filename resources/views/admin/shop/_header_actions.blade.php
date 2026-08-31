@can('create', \App\Models\Merchant::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.translate.bulk') }}" class="ajax-modal-btn btn btn-default btn-flat">
    <em class="fa fa-language"></em> {{ trans('app.bulk_translation_import') }}
  </a>

  @if (Auth::user()->isAdmin())
    <a href="javascript:void(0)" data-link="{{ route('admin.vendor.merchant.bulk') }}" class="ajax-modal-btn btn btn-default btn-flat">
      <i class="fa fa-upload"></i> {{ trans('app.bulk_import') }}
    </a>
  @endif

  <a href="javascript:void(0)" data-link="{{ route('admin.vendor.merchant.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_merchant') }}
  </a>
@endcan
