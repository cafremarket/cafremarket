<div class="form-group">
  <div class="row">
    <div class="col-sm-4 text-right">
      {!! Form::label('fb_page_id', trans('liveChat::lang.fb_page_id') . ':', ['class' => 'with-help control-label']) !!}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('liveChat::lang.shop_fb_page_id') }}"></i>
    </div>
    <div class="col-sm-8 nopadding-left">
      @if ($can_update)
        {!! Form::text('fb_page_id', $shop->fb_page_id, ['class' => 'form-control', 'placeholder' => trans('liveChat::lang.fb_page_id')]) !!}
        <div class="help-block with-errors"></div>
      @else
        <span>{{ $shop->fb_page_id }}</span>
      @endif
    </div>
  </div> <!-- /.row -->
</div>
