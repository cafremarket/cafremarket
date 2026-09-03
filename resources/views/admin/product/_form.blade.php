@php
  $title_classes = isset($product) ? 'form-control' : 'form-control makeSlug';
@endphp

@if (auth()->user()->isFromPlatform() && can_use_own_catalog_only())
  <div class="row">
    <div class="col-md-8 col-md-offset-2 mt-5">
      <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <strong><i class="icon fa fa-info-circle"></i>{{ trans('app.notice') }}</strong>
        {!! trans('messages.vendor_can_use_own_catalog_only_notice') !!}
      </div>

      <a href="{{ url('admin/setting/system/config') }}" class="btn btn-new btn-sm my-3">
        <i class="fa fa-cogs"></i> {{ trans('nav.configurations') }}
      </a>
    </div>
  </div>
@else
  <div class="row">
    <div class="col-md-12">
      <ul class="nav nav-tabs nav-justified admin-tabs product-form-tabs" style="margin-bottom: 15px;">
        <li class="active">
          <a href="#catalog_basic_tab" data-toggle="tab">
            <i class="fa fa-cube"></i> {{ trans('app.tab_basic_info') }}
          </a>
        </li>
        <li>
          <a href="#catalog_attributes_tab" data-toggle="tab">
            <i class="fa fa-tags"></i> {{ trans('app.tab_attributes_variants') }}
          </a>
        </li>
      </ul>
    </div>
  </div>

  <div class="tab-content">
    <div class="tab-pane active" id="catalog_basic_tab">
  <div class="row">
    <div class="col-md-8">
      @include('admin.partials.ui.card_start', [
        'title' => isset($product) ? trans('app.update_product') : trans('app.add_product'),
        'icon' => 'fa-cube',
        'class' => 'admin-form-section',
        'bodyClass' => '',
        'actions' => !isset($product) ? '<a href="javascript:void(0)" data-link="' . mp_route('admin.catalog.product.upload') . '" class="ajax-modal-btn btn btn-default btn-flat btn-sm">' . e(trans('app.bulk_import')) . '</a>' : null,
      ])
          <div class="row">
            <div class="col-md-9 nopadding-right">
              <div class="form-group">
                {!! Form::label('name', trans('app.form.name') . '*', ['class' => 'with-help']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.product_name') }}"></i>
                {!! Form::text('name', null, ['class' => $title_classes, 'placeholder' => trans('app.placeholder.title'), 'required']) !!}
                <div class="help-block with-errors"></div>
              </div>
            </div>

            <div class="col-md-3 nopadding-left">
              <div class="form-group">
                {!! Form::label('active', trans('app.form.status') . '*', ['class' => 'with-help']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.product_active') }}"></i>
                {!! Form::select('active', ['1' => trans('app.active'), '0' => trans('app.inactive')], !isset($product) ? 1 : null, ['class' => 'form-control select2-normal', 'placeholder' => trans('app.placeholder.status'), 'required']) !!}
                <div class="help-block with-errors"></div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 nopadding-right">
              <div class="form-group">
                {!! Form::label('mpn', trans('app.form.mpn'), ['class' => 'with-help']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.mpn') }}"></i>
                {!! Form::text('mpn', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.mpn')]) !!}
              </div>
            </div>
            <div class="col-md-4 nopadding">
              <div class="form-group">
                {!! Form::label('gtin', trans('app.form.gtin'), ['class' => 'with-help']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.gtin') }}"></i>
                {!! Form::text('gtin', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.gtin')]) !!}
              </div>
            </div>
            <div class="col-md-4 nopadding-left">
              <div class="form-group">
                {!! Form::label('gtin_type', trans('app.form.gtin_type'), ['class' => 'with-help']) !!}
                {!! Form::select('gtin_type', $gtin_types, null, ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.gtin_type')]) !!}
              </div>
            </div>
          </div>

          <div class="form-group">
            {!! Form::label('description', trans('app.form.description') . '*', ['class' => 'with-help']) !!}
            <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.product_description') }}"></i>

            @if (is_incevio_package_loaded('aiAssistant'))
              @include('aiAssistant::_generation_btn', ['ai_prompt_id' => '#name', 'ai_target_id' => '#description', 'ai_prompt_type' => \Incevio\Package\AiAssistant\Models\AiAssistantConfig::SERVICE_TYPE_DESCRIPTION, 'ai_prompt_data' => isset($product) ? $product->name : null])
            @endif

            {!! Form::textarea('description', null, ['class' => 'form-control summernote', 'rows' => '4', 'placeholder' => trans('app.placeholder.description'), 'required']) !!}
            <div class="help-block with-errors">{!! $errors->first('description', ':message') !!}</div>
          </div>

          <fieldset>
            <legend>
              {{ trans('app.form.images') }}
              <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.product_images') }}"></i>
            </legend>
            <div class="form-group">
              <div class="file-loading">
                <input id="dropzone-input" name="images[]" type="file" accept="image/*" multiple>
              </div>
              <span class="small"><i class="fa fa-info-circle"></i> {{ trans('help.multi_img_upload_instruction', ['size' => getAllowedMaxImgSize(), 'number' => getMaxNumberOfImgsForInventory(), 'dimension' => '800 x 800']) }}</span>
            </div>
          </fieldset>

          <p class="help-block">* {{ trans('app.form.required_fields') }}</p>

          <div class="box-tools pull-right admin-card__actions">
            {!! Form::submit(isset($product) ? trans('app.form.update') : trans('app.form.save'), ['class' => 'btn btn-flat btn-lg btn-primary']) !!}
          </div>
      @include('admin.partials.ui.card_end')
    </div>

    <div class="col-md-4 nopadding-left">
      @include('admin.partials.ui.card_start', [
        'title' => trans('app.organization'),
        'icon' => 'fa-sitemap',
        'class' => 'admin-form-section',
        'bodyClass' => '',
      ])
          <div class="form-group">
            {!! Form::label('category_list[]', trans('app.form.categories') . '*') !!}
            {!! Form::select('category_list[]', $categories, null, ['class' => 'form-control select2-normal', 'multiple' => 'multiple', 'required']) !!}
            <div class="help-block with-errors"></div>
            <div class="help-block text-muted">
              <i class="fa fa-tags"></i> {{ trans('help.attributes_on_next_tab') }}
            </div>
          </div>

          <fieldset class="admin-catalog-rules">
            <legend>{{ trans('app.catalog_rules') }}</legend>

            <div class="admin-catalog-rules__list">
              <label class="admin-catalog-rule">
                {{ Form::hidden('requires_shipping', 0) }}
                {!! Form::checkbox('requires_shipping', 1, !isset($product) ? 1 : null, [
                  'id' => 'requires_shipping',
                  'class' => 'admin-catalog-rule__input requires_shipping',
                ]) !!}
                <span class="admin-catalog-rule__icon admin-catalog-rule__icon--shipping" aria-hidden="true">
                  <i class="fa fa-truck"></i>
                </span>
                <span class="admin-catalog-rule__meta">
                  <span class="admin-catalog-rule__title">{{ trans('app.form.requires_shipping') }}</span>
                  <span class="admin-catalog-rule__desc">{{ trans('help.requires_shipping') }}</span>
                </span>
                <span class="admin-catalog-rule__switch" aria-hidden="true"></span>
              </label>

              <label class="admin-catalog-rule">
                {{ Form::hidden('downloadable', 0) }}
                {!! Form::checkbox('downloadable', 1, null, [
                  'id' => 'downloadable',
                  'class' => 'admin-catalog-rule__input downloadable',
                ]) !!}
                <span class="admin-catalog-rule__icon admin-catalog-rule__icon--digital" aria-hidden="true">
                  <i class="fa fa-cloud-download"></i>
                </span>
                <span class="admin-catalog-rule__meta">
                  <span class="admin-catalog-rule__title">{{ trans('app.form.downloadable') }}</span>
                  <span class="admin-catalog-rule__desc">{{ trans('help.downloadable') }}</span>
                </span>
                <span class="admin-catalog-rule__switch" aria-hidden="true"></span>
              </label>
            </div>

            @if (auth()->user()->isFromplatform())
              <div class="admin-catalog-rules__prices">
                <div class="form-group">
                  {!! Form::label('min_price', trans('app.form.catalog_min_price'), ['class' => 'with-help']) !!}
                  <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.catalog_min_price') }}"></i>
                  <div class="input-group">
                    <span class="input-group-addon">{{ get_currency_symbol() }}</span>
                    {!! Form::number('min_price', null, ['class' => 'form-control', 'step' => 'any', 'min' => '0', 'placeholder' => trans('app.placeholder.catalog_min_price')]) !!}
                  </div>
                  <div class="help-block with-errors"></div>
                </div>
                <div class="form-group">
                  {!! Form::label('max_price', trans('app.form.catalog_max_price'), ['class' => 'with-help']) !!}
                  <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.catalog_max_price') }}"></i>
                  <div class="input-group">
                    <span class="input-group-addon">{{ get_currency_symbol() }}</span>
                    {!! Form::number('max_price', null, ['class' => 'form-control', 'step' => 'any', 'min' => '0', 'placeholder' => trans('app.placeholder.catalog_max_price')]) !!}
                  </div>
                  <div class="help-block with-errors"></div>
                </div>
              </div>
            @endif
          </fieldset>

          <fieldset>
            <legend>
              {{ trans('app.featured_image') }}
              <i class="fa fa-question-circle small" data-toggle="tooltip" data-placement="top" title="{{ trans('help.product_featured_image') }}"></i>
            </legend>
            @if (isset($product) && $product->featureImage)
              <img src="{{ get_storage_file_url($product->featureImage->path, 'small') }}" alt="{{ trans('app.featured_image') }}">
              <label>
                <span style="margin-left: 10px;">
                  {!! Form::checkbox('delete_image[feature]', 1, null, ['class' => 'icheck']) !!} {{ trans('app.form.delete_image') }}
                </span>
              </label>
            @endif

            <div class="row">
              <div class="col-md-9 nopadding-right">
                <input id="uploadFile" placeholder="{{ trans('app.featured_image') }}" class="form-control" disabled="disabled" style="height: 28px;" />
              </div>
              <div class="col-md-3 nopadding-left">
                <div class="fileUpload btn btn-primary btn-block btn-flat">
                  <span>{{ trans('app.form.upload') }} </span>
                  <input type="file" name="images[feature]" id="uploadBtn" class="upload" />
                </div>
              </div>
            </div>
          </fieldset>

          <fieldset>
            <legend>{{ trans('app.branding') }}</legend>
            <div class="form-group">
              {!! Form::label('origin_country', trans('app.form.origin'), ['class' => 'with-help']) !!}
              {!! Form::select('origin_country', $countries, null, ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.origin')]) !!}
              <div class="help-block with-errors"></div>
            </div>

            <div class="form-group">
              {!! Form::label('manufacturer_id', trans('app.form.manufacturer'), ['class' => 'with-help']) !!}
              {!! Form::select('manufacturer_id', $manufacturers, null, ['class' => 'form-control select2', 'placeholder' => trans('app.placeholder.manufacturer')]) !!}
              <div class="help-block with-errors"></div>
            </div>

            <div class="form-group">
              {!! Form::label('brand', trans('app.form.brand'), ['class' => 'with-help']) !!}
              <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.brand') }}"></i>
              {!! Form::text('brand', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.brand')]) !!}
            </div>

            <div class="form-group">
              {!! Form::label('model_number', trans('app.form.model_number'), ['class' => 'with-help']) !!}
              <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.model_number') }}"></i>
              {!! Form::text('model_number', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.model_number')]) !!}
            </div>
          </fieldset>

          <fieldset>
            <legend>{{ trans('app.seo') }}</legend>
            <div class="form-group">
              {!! Form::label('slug', trans('app.form.slug') . '*', ['class' => 'with-help']) !!}
              <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.product_slug') }}"></i>
              {!! Form::text('slug', null, ['class' => 'form-control slug', 'placeholder' => trans('app.placeholder.slug'), isset($product) ? 'disabled' : 'required']) !!}
              <div class="help-block with-errors"></div>
            </div>

            <div class="form-group">
              {!! Form::label('tag_list[]', trans('app.form.tags'), ['class' => 'with-help']) !!}
              {!! Form::select('tag_list[]', $tags, null, ['class' => 'form-control select2-tag', 'multiple' => 'multiple']) !!}
            </div>
          </fieldset>
      @include('admin.partials.ui.card_end')
    </div>
  </div>
    </div>{{-- /#catalog_basic_tab --}}

    <div class="tab-pane" id="catalog_attributes_tab">
      <div class="row">
        <div class="col-md-12">
          @include('admin.partials.ui.card_start', [
            'title' => trans('app.tab_attributes_variants'),
            'icon' => 'fa-tags',
            'class' => 'admin-form-section',
            'bodyClass' => '',
          ])
            @include('admin.product._attributes_tab')

            <div class="box-tools pull-right admin-card__actions">
              {!! Form::submit(isset($product) ? trans('app.form.update') : trans('app.form.save'), ['class' => 'btn btn-flat btn-lg btn-primary']) !!}
            </div>
          @include('admin.partials.ui.card_end')
        </div>
      </div>
    </div>{{-- /#catalog_attributes_tab --}}
  </div>{{-- /.tab-content --}}
@endif
