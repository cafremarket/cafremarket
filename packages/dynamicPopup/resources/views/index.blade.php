@extends('admin.layouts.master')

@section('content')
  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">{{ trans('DynamicPopup::lang.dynamic_popups') }}</h3>
    </div> <!-- /.box-header -->
    <div class="box-body">
      <div class="row">
        <div class="col-md-6 col-md-offset-3">
          <span class="spacer20"></span>

          {!! Form::open(['route' => 'admin.appearance.popup.update', 'files' => true, 'id' => 'form', 'data-toggle' => 'validator']) !!}

          <div class="form-group">
            <div class="row">
              <div class="col-sm-4 nopadding-right">
                {!! Form::label('type', trans('DynamicPopup::lang.popup_type') . ': * ', ['class' => 'with-help control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('DynamicPopup::lang.popup_delay_time_help') }}"></i>
              </div>

              <div class="col-sm-8 nopadding-left">
                @foreach ($popup_types as $key => $value)
                  <div class="radio-inline">
                    {{ Form::radio('type', $key, $popup['type'] === $key, ['id' => $key]) }}
                    {{ Form::label($key, $value) }}
                  </div>
                @endforeach
                <div class="help-block with-errors"></div>
              </div>
            </div> <!--/.row -->
          </div> <!--/.form-group -->

          <div class="form-group">
            <div class="row">
              <div class="col-sm-4 nopadding-right">
                {!! Form::label('popup_delay_time', trans('DynamicPopup::lang.popup_delay_time') . ': * ', ['class' => 'with-help control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('DynamicPopup::lang.popup_delay_time_help') }}"></i>
              </div>

              <div class="col-sm-8 nopadding-left">
                <div class="input-group">
                  {!! Form::number('delay', $popup['delay'], ['class' => 'form-control', 'placeholder' => trans('DynamicPopup::lang.popup_delay_time'), 'required']) !!}
                  <span class="input-group-addon" id="basic-addon1">
                    {{ trans('app.miliseconds') }}
                  </span>
                </div>
                <div class="help-block with-errors"></div>
              </div>
            </div> <!--/.row -->
          </div> <!--/.form-group -->

          @isset($popup['background_img'])
            <div class="form-group text-center">
              <img src="{{ $popup['background_img'] }}" class="popup-bg-img" alt="{{ trans('app.background_image') }}">
            </div>
          @endisset

          <div class="form-group">
            <div class="row">
              <div class="col-sm-4 nopadding-right">
                <label for="exampleInputFile" class="with-help control-label"> {{ trans('app.background_image') }}</label>
              </div>

              <div class="col-md-6 nopadding">
                <input id="uploadFile2" placeholder="{{ trans('app.background_image') }}" class="form-control" disabled="disabled" style="height: 28px;" />
              </div>

              <div class="col-md-2 nopadding-left">
                <div class="fileUpload btn btn-primary btn-block btn-flat">
                  <span>{{ trans('app.form.upload') }}</span>
                  <input type="file" name="background_image" id="background_image" class="upload" />
                </div>
              </div>
            </div> <!--/.row -->
          </div> <!--/.form-group -->

          <div class="form-group">
            <div class="row">
              <div class="col-sm-4 nopadding-right">
                {!! Form::label('css', trans('DynamicPopup::lang.custom_css') . ': ', ['class' => 'with-help control-label']) !!}
                <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('DynamicPopup::lang.custom_css_help') }}"></i>
              </div>

              <div class="col-sm-8 nopadding-left">
                {!! Form::textarea('css', $popup['css'], ['class' => 'form-control', 'rows' => '6', 'placeholder' => trans('DynamicPopup::lang.custom_css_help')]) !!}
                <div class="help-block with-errors"></div>
              </div>
            </div> <!--/.row -->
          </div> <!--/.form-group -->

          <div class="row">
            <div class="col-sm-8 col-sm-offset-4">
              <p class="help-block">* {{ trans('app.form.required_fields') }}</p>
            </div>
          </div>

          <span class="spacer20"></span>

          <div class="row">
            <div class="col-sm-8 col-sm-offset-4">
              <button type="button" class="btn btn-default" data-toggle="modal" data-target="#zcart-popup-modal">
                {{ trans('app.preview') }}
              </button>

              <span class="pull-right">
                {!! Form::submit(trans('app.form.update'), ['class' => 'btn btn-flat btn-new']) !!}
              </span>
            </div>
          </div> <!--/.row -->

          {!! Form::close() !!}

          <span class="spacer50"></span>
        </div> <!--/.col-md-6 -->
      </div> <!--/.row -->
    </div> <!-- /.box-body -->
  </div> <!-- /.box -->

  <!-- Dynamic Popup -->
  @include('DynamicPopup::popup_modal')
@endsection
