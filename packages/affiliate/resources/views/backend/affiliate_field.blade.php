@include('admin.partials.ui.card_start', [
  'title' => trans('packages.affiliate.affiliate_marketing'),
  'icon' => 'fa-share-alt',
  'class' => 'admin-form-section',
  'bodyClass' => '',
  'actions' => '<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>',
])
    <div class="row">
      <div class="form-group col-md-12">
        {!! Form::label('affiliate_commission_percentage', trans('packages.affiliate.affiliate_commission'), ['class' => 'with-help']) !!}
        <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('packages.affiliate.help_commission_field') }}"></i>
        <div class="input-group">
          {!! Form::number('affiliate_commission_percentage', null, ['class' => 'form-control', 'placeholder' => trans('packages.affiliate.placeholder_commission_field'), 'step' => '0.01', 'min' => '0', 'max' => '100']) !!}
          <div class="input-group-addon">%</div>
        </div>
        <div class="help-block with-errors">
          <small class="text-info"><em class="fa fa-info-circle"> {{ trans('packages.affiliate.when_empty_commission_will_calculated_from_default') }}</em></small>
        </div>
      </div>
    </div>
@include('admin.partials.ui.card_end')
