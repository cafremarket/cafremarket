@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.pending_verifications') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.pending_verifications'),
    'icon' => 'fa-check-circle',
  ])

  @if ($merchants->isEmpty())
    <p class="text-muted">{{ trans('app.no_pending_verification_requests') }}</p>
  @else
    <table class="table table-hover admin-table">
      <thead>
        <tr>
          <th>{{ trans('app.shop_name') }}</th>
          <th>{{ trans('app.seller_type') }}</th>
          <th>{{ trans('app.owner') }}</th>
          <th>{{ trans('app.uploaded_documents') }}</th>
          <th>{{ trans('app.requested_at') }}</th>
          <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($merchants as $merchant)
          @continue(!$merchant->shop)
          <tr>
            <td>
              <div class="admin-table__cell-with-thumb">
                <img src="{{ get_storage_file_url(optional($merchant->shop->logo)->path, 'tiny') }}" class="img-circle img-sm" alt="">
                @can('view', $merchant->shop)
                  <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.show', $merchant->shop->id) }}" class="ajax-modal-btn">{{ $merchant->shop->name }}</a>
                @else
                  {{ $merchant->shop->name }}
                @endcan
              </div>
            </td>
            <td>{{ $merchant->shop->sellerTypeLabel() }}</td>
            <td>{{ optional($merchant->shop->owner)->getName() }}</td>
            <td>
              @forelse ($merchant->attachments as $attachment)
                <a href="{{ route('attachment.download', $attachment) }}"><i class="fa fa-cloud-download"></i> {{ $attachment->name }}</a>@if (!$loop->last)<br>@endif
              @empty
                <span class="text-muted">{{ trans('app.not_available') }}</span>
              @endforelse
            </td>
            <td>{{ optional($merchant->updated_at)->diffForHumans() }}</td>
            <td class="row-options admin-row-actions">
              @can('update', $merchant->shop)
                <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.verify', $merchant->shop) }}" class="ajax-modal-btn btn btn-success btn-sm btn-flat">{{ trans('app.review_verification_request') }}</a>
              @endcan
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.all_stores_verification_status'),
    'icon' => 'fa-store',
    'bodyClass' => 'responsive-table',
  ])

  @if ($unverifiedShops->isEmpty())
    <p class="text-muted">{{ trans('app.all_stores_verified') }}</p>
  @else
    <table class="table table-hover admin-table">
      <thead>
        <tr>
          <th>{{ trans('app.shop_name') }}</th>
          <th>{{ trans('app.verification') }}</th>
          <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($unverifiedShops as $shop)
          <tr>
            <td>
              <div class="admin-table__cell-with-thumb">
                <img src="{{ get_logo_url($shop, 'tiny') }}" class="img-circle img-sm" alt="">
                {{ $shop->name }}
              </div>
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
            <td class="row-options admin-row-actions">
              @can('update', $shop)
                <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.verify', $shop) }}" class="ajax-modal-btn btn btn-default btn-sm btn-flat">{{ trans('app.review_verification_request') }}</a>
              @endcan
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.rejected_verifications'),
    'icon' => 'fa-times-circle',
  ])

  @if ($rejectedMerchants->isEmpty())
    <p class="text-muted">{{ trans('app.no_rejected_verification_requests') }}</p>
  @else
    <table class="table table-hover admin-table">
      <thead>
        <tr>
          <th>{{ trans('app.shop_name') }}</th>
          <th>{{ trans('app.rejection_reason') }}</th>
          <th>{{ trans('app.rejected_at') }}</th>
          <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($rejectedMerchants as $merchant)
          @continue(!$merchant->shop)
          <tr>
            <td>
              <div class="admin-table__cell-with-thumb">
                <img src="{{ get_storage_file_url(optional($merchant->shop->logo)->path, 'tiny') }}" class="img-circle img-sm" alt="">
                {{ $merchant->shop->name }}
              </div>
            </td>
            <td>{{ \Illuminate\Support\Str::limit($merchant->verification_rejection_reason, 120) }}</td>
            <td>{{ optional($merchant->verification_rejected_at)->diffForHumans() }}</td>
            <td class="row-options admin-row-actions">
              @can('update', $merchant->shop)
                <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.verify', $merchant->shop) }}" class="ajax-modal-btn btn btn-default btn-sm btn-flat">{{ trans('app.view') }}</a>
              @endcan
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.verified_stores'),
    'icon' => 'fa-check-circle',
    'bodyClass' => 'responsive-table',
  ])

  @if ($verifiedShops->isEmpty())
    <p class="text-muted">{{ trans('app.no_verified_stores') }}</p>
  @else
    <table class="table table-hover admin-table">
      <thead>
        <tr>
          <th>{{ trans('app.shop_name') }}</th>
          <th>{{ trans('app.owner') }}</th>
          <th>{{ trans('app.verification') }}</th>
          <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($verifiedShops as $shop)
          <tr>
            <td>
              <div class="admin-table__cell-with-thumb">
                <img src="{{ get_logo_url($shop, 'tiny') }}" class="img-circle img-sm" alt="">
                {{ $shop->name }}
              </div>
            </td>
            <td>{{ optional($shop->owner)->getName() }}</td>
            <td><span class="label label-success">{{ trans('app.verified') }}</span></td>
            <td class="row-options admin-row-actions">
              @can('update', $shop)
                <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.verify', $shop) }}" class="ajax-modal-btn btn btn-default btn-sm btn-flat">{{ trans('app.view') }}</a>
                <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.verify.revert.form', $shop) }}" class="ajax-modal-btn btn btn-warning btn-sm btn-flat">{{ trans('app.revert_verification') }}</a>
              @endcan
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  @include('admin.partials.ui.card_end')
@endsection
