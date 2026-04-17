<div class="form-group">
  {!! Form::label('default_affiliate_commission_percentage', trans('packages.affiliate.default_affiliate_commission_percentage') . ':', ['class' => 'with-help col-sm-4 control-label']) !!}

  <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('packages.affiliate.help_default_affiliate_commission_percentage') }}"></i>

  <div class="col-sm-7 nopadding-left">
    @if ($can_update)
      <div class="input-group">
        {!! Form::number('default_affiliate_commission_percentage', $config->default_affiliate_commission_percentage, ['min' => 0, 'max' => 100, 'step' => 0.01, 'class' => 'form-control', 'placeholder' => trans('packages.affiliate.placeholder_default_affiliate_commission_percentage')]) !!}
        <div class="input-group-addon">%</div>
      </div>
    @else
      <span>{{ $config->default_affiliate_commission_percentage }}%</span>
    @endif
    
    <div class="help-block with-errors"></div>
  </div>
</div>
