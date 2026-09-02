@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.address_change_request') }} — {{ $addressChangeRequest->shop->name }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.address_change_request'),
    'icon' => 'fa-map-marker',
  ])

  <div class="row mb-4">
    <div class="col-md-6">
      <p><strong>{{ trans('app.shop_name') }}:</strong> {{ $addressChangeRequest->shop->name }}</p>
      <p><strong>{{ trans('app.owner') }}:</strong> {{ optional($addressChangeRequest->shop->owner)->getName() }}</p>
      <p><strong>{{ trans('app.requested_by') }}:</strong> {{ optional($addressChangeRequest->requester)->getName() ?? trans('app.not_available') }}</p>
      <p><strong>{{ trans('app.requested_at') }}:</strong> {{ $addressChangeRequest->created_at->toDayDateTimeString() }}</p>
    </div>
    <div class="col-md-6 text-right">
      @can('update', $addressChangeRequest->shop)
        @if ($addressChangeRequest->isPending())
          <form action="{{ route('admin.vendor.shop.addressChangeRequests.approve', $addressChangeRequest) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-success confirm">
              <i class="fa fa-check"></i> {{ trans('app.approve') }}
            </button>
          </form>
        @endif
      @endcan
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="box box-default">
        <div class="box-header with-border">
          <h3 class="box-title">{{ trans('app.previous_address') }}</h3>
        </div>
        <div class="box-body">
          @include('admin.partials._address_details', ['address' => $previousAddress])
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">{{ trans('app.new_address') }}</h3>
        </div>
        <div class="box-body">
          @include('admin.partials._address_details', ['address' => $requestedAddress])
        </div>
      </div>
    </div>
  </div>

  @if ($addressChangeRequest->isPending())
    @can('update', $addressChangeRequest->shop)
      <div class="box box-danger mt-3">
        <div class="box-header with-border">
          <h3 class="box-title">{{ trans('app.reject') }}</h3>
        </div>
        <div class="box-body">
          {!! Form::open(['route' => ['admin.vendor.shop.addressChangeRequests.reject', $addressChangeRequest], 'method' => 'POST']) !!}
            <div class="form-group">
              {!! Form::label('rejection_reason', trans('app.rejection_reason') . '*') !!}
              {!! Form::textarea('rejection_reason', null, ['class' => 'form-control', 'rows' => 3, 'required', 'maxlength' => 1000]) !!}
            </div>
            <button type="submit" class="btn btn-danger confirm">
              <i class="fa fa-times"></i> {{ trans('app.reject') }}
            </button>
          {!! Form::close() !!}
        </div>
      </div>
    @endcan
  @elseif ($addressChangeRequest->status === 'rejected' && $addressChangeRequest->rejection_reason)
    <div class="alert alert-warning mt-3">
      <strong>{{ trans('app.rejection_reason') }}:</strong> {{ $addressChangeRequest->rejection_reason }}
    </div>
  @endif

  <div class="mt-3">
    <a href="{{ route('admin.vendor.shop.addressChangeRequests') }}" class="btn btn-default">
      <i class="fa fa-arrow-left"></i> {{ trans('app.back') }}
    </a>
  </div>

  @include('admin.partials.ui.card_end')
@endsection
