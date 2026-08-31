@can('create', \App\Models\Slider::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.appearance.slider.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_slider') }}
  </a>
@endcan
