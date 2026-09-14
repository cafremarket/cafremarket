@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.policy_pages') }}
@endsection

@section('content')
  @include('admin.partials.ui.card_start', [
    'title' => trans('app.policy_pages'),
    'icon' => 'fa-balance-scale',
    'actions' => view('admin.policy_page._header_actions')->render(),
  ])

  <p class="text-muted" style="margin-bottom: 20px;">
    {{ trans('help.policy_pages_intro') }}
  </p>

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.page_title') }}</th>
        <th>{{ trans('app.slug') }}</th>
        <th>{{ trans('app.used_by') }}</th>
        <th>{{ trans('app.status') }}</th>
        <th>{{ trans('app.updated_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($policyPages as $row)
        @php
          $page = $row['page'];
          $status = $row['status'];
          $statusLabels = [
            'published' => ['label-success', trans('app.ready')],
            'placeholder' => ['label-warning', trans('app.placeholder_content')],
            'draft' => ['label-default', strtoupper(trans('app.draft'))],
            'missing' => ['label-danger', trans('app.missing')],
          ];
          [$statusClass, $statusText] = $statusLabels[$status] ?? ['label-default', $status];
        @endphp
        <tr>
          <td width="22%">
            <strong>{{ $row['meta']['title'] }}</strong>
            @if ($page)
              <br><span class="text-muted small">{!! $page->title !!}</span>
            @endif
            <br><span class="small text-muted">{{ $row['meta']['description'] }}</span>
          </td>
          <td>
            <code>{{ $row['slug'] }}</code>
            <br><span class="small text-muted">{{ $row['api_path'] }}</span>
          </td>
          <td>
            @foreach ($row['meta']['channels'] as $channel)
              <span class="label label-outline" style="display:inline-block;margin:2px 2px 2px 0;">{{ $channel }}</span>
            @endforeach
          </td>
          <td>
            <span class="label {{ $statusClass }}">{{ $statusText }}</span>
            @if ($page)
              <br>{!! $page->visibilityName() !!}
            @endif
          </td>
          <td class="small">
            @if ($page)
              {{ $page->updated_at->diffForHumans() }}
              @if ($page->author)
                <br>{{ $page->author->getName() }}
              @endif
            @else
              —
            @endif
          </td>
          <td class="row-options admin-row-actions">
            @if ($row['web_url'])
              <a href="{{ $row['web_url'] }}" target="_blank" class="admin-action-btn" title="{{ trans('app.go_to_page') }}" data-toggle="tooltip"><i class="fa fa-external-link"></i></a>
            @endif
            <a href="{{ route('admin.utility.policyPage.edit', $row['slug']) }}" class="admin-action-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
