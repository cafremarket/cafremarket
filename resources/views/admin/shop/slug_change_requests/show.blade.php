@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.slug_change_request') }} — {{ $slugChangeRequest->shop->name }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.slug_change_request'),
    'icon' => 'fa-link',
  ])

  <div class="row mb-4">
    <div class="col-md-6">
      <p><strong>{{ trans('app.shop_name') }}:</strong> {{ $slugChangeRequest->shop->name }}</p>
      <p><strong>{{ trans('app.owner') }}:</strong> {{ optional($slugChangeRequest->shop->owner)->getName() }}</p>
      <p><strong>{{ trans('app.requested_by') }}:</strong> {{ optional($slugChangeRequest->requester)->getName() ?? trans('app.not_available') }}</p>
      <p><strong>{{ trans('app.requested_at') }}:</strong> {{ $slugChangeRequest->created_at->toDayDateTimeString() }}</p>
    </div>
    <div class="col-md-6 text-right">
      @can('update', $slugChangeRequest->shop)
        @if ($slugChangeRequest->isPending())
          <form action="{{ route('admin.vendor.shop.slugChangeRequests.approve', $slugChangeRequest) }}" method="POST" class="d-inline">
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
          <h3 class="box-title">{{ trans('app.current_slug') }}</h3>
        </div>
        <div class="box-body">
          <p><code>{{ $slugChangeRequest->previous_slug }}</code></p>
          <p class="text-muted">{{ get_shop_url($slugChangeRequest->shop_id) }}</p>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">{{ trans('app.requested_slug') }}</h3>
        </div>
        <div class="box-body">
          <p><code>{{ $slugChangeRequest->requested_slug }}</code></p>
          <p class="text-muted">{{ url('shop/'.$slugChangeRequest->requested_slug) }}</p>
        </div>
      </div>
    </div>
  </div>

  @if ($slugChangeRequest->isPending())
    @can('update', $slugChangeRequest->shop)
      <div class="box box-danger mt-3">
        <div class="box-header with-border">
          <h3 class="box-title">{{ trans('app.reject') }}</h3>
        </div>
        <div class="box-body">
          {!! Form::open(['route' => ['admin.vendor.shop.slugChangeRequests.reject', $slugChangeRequest], 'method' => 'POST']) !!}
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
  @elseif ($slugChangeRequest->status === 'rejected' && $slugChangeRequest->rejection_reason)
    <div class="alert alert-warning mt-3">
      <strong>{{ trans('app.rejection_reason') }}:</strong> {{ $slugChangeRequest->rejection_reason }}
    </div>
  @endif

  <div class="mt-3">
    <a href="{{ route('admin.vendor.shop.slugChangeRequests') }}" class="btn btn-default">
      <i class="fa fa-arrow-left"></i> {{ trans('app.back') }}
    </a>
  </div>

  @include('admin.partials.ui.card_end')
@endsection
