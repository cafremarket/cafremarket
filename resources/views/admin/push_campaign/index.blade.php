@extends('admin.layouts.master')

@section('page_title')
  {{ trans('nav.push_notifications') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('nav.push_notifications'),
    'icon' => 'fa-bullhorn',
    'actions' => view('admin.push_campaign._header_actions')->render(),
    'bodyClass' => '',
  ])

  <div class="row admin-push-stats">
    <div class="col-sm-3">
      <div class="info-box bg-aqua">
        <span class="info-box-icon"><i class="fa fa-users"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Customer devices</span>
          <span class="info-box-number">{{ $counts['customers'] }}</span>
        </div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="info-box bg-green">
        <span class="info-box-icon"><i class="fa fa-store"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Vendor devices</span>
          <span class="info-box-number">{{ $counts['vendors'] }}</span>
        </div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="info-box bg-yellow">
        <span class="info-box-icon"><i class="fa fa-motorcycle"></i></span>
        <div class="info-box-content">
          <span class="info-box-text">Delivery devices</span>
          <span class="info-box-number">{{ $counts['delivery'] }}</span>
        </div>
      </div>
    </div>
    <div class="col-sm-3">
      <div class="admin-push-fcm-status">
        <strong>FCM status</strong>
        <small>
          Driver: {{ $status['driver'] }}<br>
          Customer ({{ $status['customer_project'] ?? '?' }}):
          v1 {{ $status['customer_v1'] ? 'OK' : '—' }} /
          legacy {{ $status['customer_legacy'] ? 'OK' : '—' }}<br>
          Vendor ({{ $status['vendor_project'] ?? '?' }}):
          v1 {{ $status['vendor_v1'] ? 'OK' : '—' }} /
          legacy {{ $status['vendor_legacy'] ? 'OK' : '—' }}<br>
          Delivery ({{ $status['delivery_project'] ?? '?' }}):
          v1 {{ !empty($status['delivery_v1']) ? 'OK' : '—' }} /
          legacy {{ !empty($status['delivery_legacy']) ? 'OK' : '—' }}
        </small>
      </div>
    </div>
  </div>

  @if (! $status['customer_v1'] && ! $status['customer_legacy'] && ! $status['vendor_v1'] && ! $status['vendor_legacy'])
    <div class="admin-alert admin-alert--warning">
      No FCM credentials configured. Add Firebase service account JSON files under
      <code>storage/app/firebase/</code> or set
      <code>FCM_TOKEN_CUSTOMER</code> / <code>FCM_TOKEN_VENDOR</code> in <code>.env</code>.
    </div>
  @endif

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>Title</th>
        <th>Audience</th>
        <th>Type</th>
        <th>Status</th>
        <th>Sent / Failed</th>
        <th>Created</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($campaigns as $campaign)
        <tr>
          <td>
            <a href="javascript:void(0)" data-link="{{ route('admin.promotion.push_campaign.show', $campaign) }}" class="ajax-modal-btn">
              <strong>{{ $campaign->title }}</strong>
            </a>
            <br><span class="text-muted small">{{ \Illuminate\Support\Str::limit($campaign->body, 80) }}</span>
          </td>
          <td>{{ $campaign->audience }}</td>
          <td>{{ $campaign->type }}</td>
          <td>
            @php
              $label = ['draft' => 'default', 'queued' => 'info', 'sending' => 'warning', 'sent' => 'success', 'failed' => 'danger'][$campaign->status] ?? 'default';
            @endphp
            <span class="label label-{{ $label }}">{{ strtoupper($campaign->status) }}</span>
          </td>
          <td>
            {{ $campaign->sent_count }} / {{ $campaign->failed_count }}
            <br><small class="text-muted">targets: {{ $campaign->target_count }}</small>
          </td>
          <td class="small">{{ optional($campaign->created_at)->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @if (in_array($campaign->status, ['draft', 'failed']))
              <a href="javascript:void(0)" data-link="{{ route('admin.promotion.push_campaign.edit', $campaign) }}" class="admin-action-btn ajax-modal-btn" title="Edit" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
              {!! Form::open(['route' => ['admin.promotion.push_campaign.send', $campaign], 'method' => 'post', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="Send now" data-toggle="tooltip"><i class="fa fa-paper-plane"></i></button>
              {!! Form::close() !!}
            @endif
            {!! Form::open(['route' => ['admin.promotion.push_campaign.destroy', $campaign], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
            <button type="submit" class="admin-action-btn confirm ajax-silent" title="Delete" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
            {!! Form::close() !!}
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  {{ $campaigns->links() }}

  @include('admin.partials.ui.card_end')
@endsection
