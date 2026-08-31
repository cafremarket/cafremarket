@if ($can_update ?? false)
  <a href="{{ route('admin.vendor.shop.translate.form', ['shop' => $shop, 'language' => $translation_language ?? app()->getLocale()]) }}" class="btn btn-default btn-flat">
    <em class="fa fa-language"></em> {{ trans('app.manage_translations') }}
  </a>
@endif
