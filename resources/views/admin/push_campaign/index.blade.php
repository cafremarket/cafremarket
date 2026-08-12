@extends('admin.layouts.master')

@section('content')
  <div class="row">
    <div class="col-md-12">
      <div class="box">
        <div class="box-header with-border">
          <h3 class="box-title">{{ trans('nav.push_notifications') }}</h3>
          <div class="box-tools pull-right">
            <a href="javascript:void(0)" data-link="{{ route('admin.promotion.push_campaign.create') }}"
               class="ajax-modal-btn btn btn-new btn-flat">
              <i class="fa fa-plus"></i> New campaign
            </a>
          </div>
        </div>

        <div class="box-body">
          <div class="row" style="margin-bottom: 15px;">
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
              <div class="well well-sm" style="margin:0; min-height: 70px;">
                <strong>FCM status</strong><br>
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
            <div class="alert alert-warning">
              No FCM credentials configured. Add Firebase service account JSON files under
              <code>storage/app/firebase/</code> (recommended for iOS + Android) or set
              <code>FCM_TOKEN_CUSTOMER</code> / <code>FCM_TOKEN_VENDOR</code> in <code>.env</code>.
            </div>
          @endif

          <table class="table table-hover table-no-sort">
            <thead>
              <tr>
                <th>Title</th>
                <th>Audience</th>
                <th>Type</th>
                <th>Status</th>
                <th>Sent / Failed</th>
                <th>Created</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse ($campaigns as $campaign)
                <tr>
                  <td>
                    <a href="javascript:void(0)"
                       data-link="{{ route('admin.promotion.push_campaign.show', $campaign) }}"
                       class="ajax-modal-btn">
                      <strong>{{ $campaign->title }}</strong>
                    </a>
                    <br><span class="text-muted small">{{ \Illuminate\Support\Str::limit($campaign->body, 80) }}</span>
                  </td>
                  <td>{{ $campaign->audience }}</td>
                  <td>{{ $campaign->type }}</td>
                  <td>
                    @php
                      $label = [
                        'draft' => 'default',
                        'queued' => 'info',
                        'sending' => 'warning',
                        'sent' => 'success',
                        'failed' => 'danger',
                      ][$campaign->status] ?? 'default';
                    @endphp
                    <span class="label label-{{ $label }}">{{ strtoupper($campaign->status) }}</span>
                  </td>
                  <td>{{ $campaign->sent_count }} / {{ $campaign->failed_count }}
                    <br><small class="text-muted">targets: {{ $campaign->target_count }}</small>
                  </td>
                  <td class="small">{{ optional($campaign->created_at)->diffForHumans() }}</td>
                  <td class="row-options text-muted small text-right">
                    @if (in_array($campaign->status, ['draft', 'failed']))
                      <a href="javascript:void(0)"
                         data-link="{{ route('admin.promotion.push_campaign.edit', $campaign) }}"
                         class="ajax-modal-btn" title="Edit"><i class="fa fa-edit"></i></a>
                      &nbsp;
                      {!! Form::open(['route' => ['admin.promotion.push_campaign.send', $campaign], 'method' => 'post', 'class' => 'data-form', 'style' => 'display:inline']) !!}
                      {!! Form::button('<i class="fa fa-paper-plane"></i>', ['type' => 'submit', 'class' => 'confirm ajax-silent', 'title' => 'Send now']) !!}
                      {!! Form::close() !!}
                      &nbsp;
                    @endif
                    {!! Form::open(['route' => ['admin.promotion.push_campaign.destroy', $campaign], 'method' => 'delete', 'class' => 'data-form', 'style' => 'display:inline']) !!}
                    {!! Form::button('<i class="fa fa-trash-o"></i>', ['type' => 'submit', 'class' => 'confirm ajax-silent', 'title' => 'Delete']) !!}
                    {!! Form::close() !!}
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted">No push campaigns yet. Create one to send promotions.</td>
                </tr>
              @endforelse
            </tbody>
          </table>

          {{ $campaigns->links() }}
        </div>
      </div>
    </div>
  </div>
@endsection
