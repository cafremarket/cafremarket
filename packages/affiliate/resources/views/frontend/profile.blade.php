@extends('affiliate::backend.master_layout')

@section('page-style')
  @include('plugins.ionic')
@endsection

@section('content')
  <div class="box">
    <div class="box-header with-border text-center">
      <div class="box-title">
            {{ trans('app.profile') }}
      </div>
    </div>
    <div class="box-body">
        {!! Form::model($affiliate, ['route' => ['affiliate.profile.update', $affiliate], 'method' => 'put', 'id' => 'form', 'data-toggle' => 'validator', 'files' => true]) !!}
        <div class="row">
          <div class="col-md-6 col-md-offset-3 pb-4">
            <div class="form-group">
                {!! Form::label('name', trans('app.name') . '*') !!}
                {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => trans('theme.placeholder.full_name'), 'required']) !!}
            </div>
    
            <div class="form-group">
                {!! Form::label('username', trans('packages.affiliate.username') . '*') !!}
                {!! Form::text('username', null, ['class' => 'form-control', 'id' => 'js-username', 'placeholder' => trans('packages.affiliate.placeholder_username'), 'required']) !!}
                <span id="js-username-feedback"></span>
            </div>
            
            <div class="form-group">
                {!! Form::label('email', trans('app.email') . '*') !!}
                {!! Form::email('email', null, ['class' => 'form-control', 'placeholder' => trans('theme.placeholder.valid_email'), 'required']) !!}
            </div>
    
            <div class="form-group">
                {!! Form::label('phone', trans('app.phone_number')) !!}
                {!! Form::text('phone', null, ['class' => 'form-control', 'placeholder' => trans('app.placeholder.phone_number')]) !!}
            </div>
            {!! Form::submit(trans('app.update'), ['class' => 'btn btn-primary']) !!}
          </div>

          <div class="form-group text-center m-2">
            <a class="ajax-modal-btn btn btn-new btn-flat" href="javascript:void(0)" data-link="{{ route('affiliate.profile.passwordForm') }}"><i class="fa fa-lock"></i> {{ trans('app.change_password') }}</a>
          </div>
        </div>
        {!! Form::close() !!}
    </div>
  </div>
@endsection

@section('page-script')
  @include('affiliate::scripts.affiliate_username_validation')
@endsection