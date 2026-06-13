@extends('admin.layouts.master')

@section('content')
  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">{{ trans('app.pending_verifications') }}</h3>
    </div>
    <div class="box-body">
      @if ($merchants->isEmpty())
        <p class="text-muted">{{ trans('app.no_pending_verification_requests') }}</p>
      @else
        <table class="table table-hover table-option">
          <thead>
            <tr>
              <th>{{ trans('app.shop_name') }}</th>
              <th>{{ trans('app.owner') }}</th>
              <th>{{ trans('app.uploaded_documents') }}</th>
              <th>{{ trans('app.requested_at') }}</th>
              <th>{{ trans('app.option') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($merchants as $merchant)
              @continue(!$merchant->shop)

              <tr>
                <td>
                  <img src="{{ get_storage_file_url(optional($merchant->shop->logo)->path, 'tiny') }}" class="img-circle img-sm" alt="{{ trans('app.logo') }}">
                  <p class="indent10">
                    @can('view', $merchant->shop)
                      <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.show', $merchant->shop->id) }}" class="ajax-modal-btn">{{ $merchant->shop->name }}</a>
                    @else
                      {{ $merchant->shop->name }}
                    @endcan
                  </p>
                </td>
                <td>{{ optional($merchant->shop->owner)->getName() }}</td>
                <td>
                  @forelse ($merchant->attachments as $attachment)
                    <a href="{{ route('attachment.download', $attachment) }}">
                      <i class="fa fa-cloud-download"></i> {{ $attachment->name }}
                    </a>
                    @if (!$loop->last)
                      <br />
                    @endif
                  @empty
                    <span class="text-muted">{{ trans('app.not_available') }}</span>
                  @endforelse
                </td>
                <td>{{ optional($merchant->updated_at)->diffForHumans() }}</td>
                <td class="row-options">
                  @can('update', $merchant->shop)
                    <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.verify', $merchant->shop) }}" class="ajax-modal-btn btn btn-success btn-sm btn-flat">{{ trans('app.review_verification_request') }}</a>
                  @endcan
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>
  </div>

  <div class="box">
    <div class="box-header with-border">
      <h3 class="box-title">{{ trans('app.all_stores_verification_status') }}</h3>
    </div>
    <div class="box-body responsive-table">
      @if ($unverifiedShops->isEmpty())
        <p class="text-muted">{{ trans('app.all_stores_verified') }}</p>
      @else
        <table class="table table-hover table-option">
          <thead>
            <tr>
              <th>{{ trans('app.shop_name') }}</th>
              <th>{{ trans('app.verification') }}</th>
              <th>{{ trans('app.option') }}</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($unverifiedShops as $shop)
              <tr>
                <td>
                  <img src="{{ get_logo_url($shop, 'tiny') }}" class="img-circle img-sm" alt="{{ trans('app.logo') }}">
                  <span class="indent10">{{ $shop->name }}</span>
                </td>
                <td>
                  @if (optional($shop->config)->pending_verification)
                    <span class="label label-warning">{{ trans('app.verification_pending') }}</span>
                  @elseif (optional($shop->config)->verification_rejected_at)
                    <span class="label label-danger">{{ trans('app.verification_rejected') }}</span>
                  @else
                    <span class="label label-default">{{ $shop->getVerificationStatus() }}</span>
                  @endif
                </td>
                <td class="row-options">
                  @can('update', $shop)
                    <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.verify', $shop) }}" class="ajax-modal-btn btn btn-default btn-sm btn-flat">{{ trans('app.review_verification_request') }}</a>
                  @endcan
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>
  </div>
@endsection
