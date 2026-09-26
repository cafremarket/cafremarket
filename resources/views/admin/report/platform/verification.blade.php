@extends('admin.layouts.master')

@section('page_title')
  {{ trans('verification_report.title') }}
@endsection

@php
  $badge = function ($state) {
      $map = [
          'ok' => ['success', trans('verification_report.state_ok')],
          'issue' => ['danger', trans('verification_report.state_issue')],
          'pending' => ['warning', trans('verification_report.state_pending')],
      ];

      return '<span class="label label-' . $map[$state][0] . '">' . e($map[$state][1]) . '</span>';
  };
  $mailState = $mail['status'] === 'working' ? 'ok' : ($mail['status'] === 'failing' ? 'issue' : 'pending');
@endphp

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('verification_report.checks'),
    'icon' => 'fa-shield',
  ])

  <table class="table admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('verification_report.check') }}</th>
        <th>{{ trans('app.status') }}</th>
        <th>{{ trans('verification_report.details') }}</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><strong>{{ trans('verification_report.email_verification') }}</strong></td>
        <td>{!! $badge($mailState) !!}</td>
        <td>
          @if ($mail['status'] === 'working')
            {{ trans('verification_report.email_working') }}
          @elseif ($mail['status'] === 'failing')
            {{ trans('verification_report.email_failing') }}
            @if ($mail['last_failure'])
              <br><small class="text-muted">{{ $mail['last_failure']->created_at }} — {{ \Illuminate\Support\Str::limit($mail['last_failure']->error, 200) }}</small>
            @endif
            <br><a href="{{ route('admin.utility.emailLog.index', ['status' => 'failed']) }}">{{ trans('nav.email_logs') }} &rarr;</a>
          @else
            {{ trans('verification_report.email_not_configured') }}
          @endif
        </td>
      </tr>
      <tr>
        <td><strong>{{ trans('verification_report.phone_verification') }}</strong></td>
        <td>{!! $badge($smsConfigured ? 'ok' : 'pending') !!}</td>
        <td>{{ trans($smsConfigured ? 'verification_report.sms_working' : 'verification_report.sms_not_configured') }}</td>
      </tr>
      <tr>
        <td><strong>{{ trans('verification_report.recaptcha') }}</strong></td>
        <td>{!! $badge(! $recaptchaConfigured ? 'pending' : ($recaptchaFailure ? 'issue' : 'ok')) !!}</td>
        <td>
          @if (! $recaptchaConfigured)
            {{ trans('verification_report.recaptcha_not_configured') }}
          @else
            {{ trans('verification_report.recaptcha_working') }}
            @if ($recaptchaFailure)
              <br><small class="text-danger">{{ trans('verification_report.recaptcha_last_failure', ['at' => $recaptchaFailure['at']]) }}: {{ \Illuminate\Support\Str::limit($recaptchaFailure['error'], 200) }}</small>
            @endif
          @endif
        </td>
      </tr>
      <tr>
        <td><strong>{{ trans('verification_report.email_quality') }}</strong></td>
        <td>{!! $badge('ok') !!}</td>
        <td>{{ trans('verification_report.email_quality_details') }}</td>
      </tr>
      <tr>
        <td><strong>{{ trans('verification_report.phone_quality') }}</strong></td>
        <td>{!! $badge('ok') !!}</td>
        <td>{{ trans('verification_report.phone_quality_details') }}</td>
      </tr>
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.card_start', [
    'title' => trans('verification_report.last_30_days'),
    'icon' => 'fa-users',
  ])

  <table class="table admin-table table-no-sort">
    <thead>
      <tr>
        <th></th>
        <th>{{ trans('verification_report.new_signups') }}</th>
        <th>{{ trans('verification_report.pending_verification') }}</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ trans('app.customers') }}</td>
        <td>{{ $customers['new'] }}</td>
        <td>{{ $customers['unverified'] }}</td>
      </tr>
      <tr>
        <td>{{ trans('app.merchants') }}</td>
        <td>{{ $vendors['new'] }}</td>
        <td>{{ $vendors['unverified'] }}</td>
      </tr>
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.card_start', [
    'title' => trans('verification_report.suspects_title', ['count' => count($suspects)]),
    'icon' => 'fa-user-secret',
    'actions' => '<a href="' . e(route('admin.report.verification', ['refresh' => 1])) . '" class="btn btn-default btn-sm btn-flat"><i class="fa fa-refresh"></i> ' . e(trans('verification_report.rescan')) . '</a>',
  ])

  <p class="text-muted">{{ trans('verification_report.suspects_help') }}</p>

  @if (count($suspects))
    <p><code>php artisan accounts:clean-fake</code> &mdash; {{ trans('verification_report.cleanup_dry_run') }}<br>
      <code>php artisan accounts:clean-fake --apply</code> &mdash; {{ trans('verification_report.cleanup_apply') }}</p>

    <table class="table table-hover admin-table table-no-sort">
      <thead>
        <tr>
          <th>{{ trans('app.name') }}</th>
          <th>{{ trans('app.email') }}</th>
          <th>{{ trans('verification_report.score') }}</th>
          <th>{{ trans('verification_report.why') }}</th>
          <th>{{ trans('app.date') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($suspects as $row)
          <tr>
            <td><a href="javascript:void(0)" data-link="{{ route('admin.admin.customer.show', $row['id']) }}" class="ajax-modal-btn modal-btn">{{ $row['name'] }}</a></td>
            <td>{{ $row['email'] }}</td>
            <td><span class="label label-danger">{{ $row['score'] }}</span></td>
            <td><small>{{ implode('; ', $row['reasons']) }}</small></td>
            <td>{{ $row['created_at'] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <p>{{ trans('verification_report.no_suspects') }}</p>
  @endif

  @include('admin.partials.ui.card_end')

  @if (count($suspectVendors))
    @include('admin.partials.ui.card_start', [
      'title' => trans('verification_report.suspect_vendors_title'),
      'icon' => 'fa-shopping-bag',
    ])

    <p class="text-muted">{{ trans('verification_report.suspect_vendors_help') }}</p>

    <table class="table admin-table table-no-sort">
      <thead>
        <tr>
          <th>{{ trans('app.name') }}</th>
          <th>{{ trans('app.email') }}</th>
          <th>{{ trans('verification_report.why') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($suspectVendors as $row)
          <tr>
            <td>{{ $row['name'] }}</td>
            <td>{{ $row['email'] }}</td>
            <td><small>{{ implode('; ', $row['reasons']) }}</small></td>
          </tr>
        @endforeach
      </tbody>
    </table>

    @include('admin.partials.ui.card_end')
  @endif

  @if ($unverifiedCustomers->isNotEmpty())
    @include('admin.partials.ui.card_start', [
      'title' => trans('verification_report.unverified_customers'),
      'icon' => 'fa-user-times',
    ])

    <table class="table table-hover admin-table table-no-sort">
      <thead>
        <tr>
          <th>{{ trans('app.name') }}</th>
          <th>{{ trans('app.email') }}</th>
          <th>{{ trans('app.date') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($unverifiedCustomers as $customer)
          <tr>
            <td><a href="javascript:void(0)" data-link="{{ route('admin.admin.customer.show', $customer->id) }}" class="ajax-modal-btn modal-btn">{{ $customer->name }}</a></td>
            <td>{{ $customer->email }}</td>
            <td>{{ $customer->created_at }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    @include('admin.partials.ui.card_end')
  @endif

  @if ($recentMailFailures->isNotEmpty())
    @include('admin.partials.ui.card_start', [
      'title' => trans('verification_report.recent_mail_failures'),
      'icon' => 'fa-envelope-o',
    ])

    <table class="table admin-table table-no-sort">
      <thead>
        <tr>
          <th>{{ trans('app.date') }}</th>
          <th>{{ trans('app.to') }}</th>
          <th>{{ trans('verification_report.details') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($recentMailFailures as $log)
          <tr>
            <td>{{ $log->created_at }}</td>
            <td>{{ $log->to }}</td>
            <td><small>{{ \Illuminate\Support\Str::limit($log->error, 160) }}</small></td>
          </tr>
        @endforeach
      </tbody>
    </table>

    @include('admin.partials.ui.card_end')
  @endif

  @if (count($recentIssues))
    @include('admin.partials.ui.card_start', [
      'title' => trans('verification_report.recent_issues'),
      'icon' => 'fa-exclamation-triangle',
    ])

    <p class="text-muted">{{ trans('verification_report.recent_issues_help') }}</p>

    <table class="table admin-table table-no-sort">
      <thead>
        <tr>
          <th>{{ trans('app.date') }}</th>
          <th>{{ trans('verification_report.area') }}</th>
          <th>{{ trans('verification_report.details') }}</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($recentIssues as $issue)
          <tr>
            <td>{{ $issue['at'] }}</td>
            <td>{{ $issue['area'] }}</td>
            <td><small>{{ \Illuminate\Support\Str::limit($issue['message'], 200) }}</small></td>
          </tr>
        @endforeach
      </tbody>
    </table>

    @include('admin.partials.ui.card_end')
  @endif
@endsection
