@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.merchants') }}
@endsection

@section('content')
  @php
    $merchantModel = \App\Models\Merchant::class;
    $massActions = [
      ['url' => route('admin.vendor.shop.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.vendor.shop.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.merchants'),
    'icon' => 'fa-briefcase',
    'actions' => view('admin.merchant._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $merchantModel, 'massActions' => $massActions])
        <th>{{ trans('app.avatar') }}</th>
        <th>{{ trans('app.nice_name') }}</th>
        <th>{{ trans('app.full_name') }}</th>
        <th>{{ trans('app.shop') }}</th>
        @if (is_subscription_enabled())
          <th>{{ trans('app.current_billing_plan') }}</th>
        @endif
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($merchants as $merchant)
        <tr>
          @can('massDelete', $merchantModel)
            <td><input id="{{ $merchant->owns->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td>
            <img src="{{ get_avatar_src($merchant, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt="">
          </td>
          <td>
            {{ $merchant->nice_name }}
            @unless ($merchant->active)
              <span class="label label-default">{{ trans('app.inactive') }}</span>
            @endunless
          </td>
          <td>{{ $merchant->name }}</td>
          <td>
            @if ($merchant->owns->name)
              <div class="admin-table__shop-cell">
                <img src="{{ get_storage_file_url(optional($merchant->owns->logoImage)->path, 'tiny') }}" class="img-circle img-sm" alt="">
                <div>
                  <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.show', $merchant->owns->id) }}" class="ajax-modal-btn">
                    {{ $merchant->owns->name }}
                  </a>
                  @if ($merchant->owns->isVerified())
                    <img src="{{ get_verified_badge() }}" class="verified-badge img-xs" data-toggle="tooltip" title="{{ trans('help.verified_seller') }}" alt="">
                  @endif
                  @if ($merchant->owns->deleted_at)
                    <span class="label label-default"><i class="fa fa-trash-o"></i> {{ trans('app.in_trash') }}</span>
                  @endif
                  @if ($merchant->owns->isDown())
                    <span class="label label-default">{{ trans('app.maintenance_mode') }}</span>
                  @elseif(!$merchant->owns->active)
                    <span class="label label-default">{{ trans('app.inactive') }}</span>
                  @endif
                </div>
              </div>
            @endif
          </td>
          @if (is_subscription_enabled())
            <td>
              {{ optional($merchant->owns)->plan->name }}
              @if ($merchant->owns->onTrial())
                <span class="label label-info">{{ trans('app.trialing') }}</span>
              @endif
            </td>
          @endif
          <td class="row-options admin-row-actions">
            @include('admin.merchant._row_actions', ['merchant' => $merchant])
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  {{-- Trash --}}
  @include('admin.partials.ui.trash_start', ['title' => trans('app.trash')])

  <table class="table table-hover admin-table table-no-sort">
    <thead>
      <tr>
        <th>{{ trans('app.avatar') }}</th>
        <th>{{ trans('app.nice_name') }}</th>
        <th>{{ trans('app.full_name') }}</th>
        <th>{{ trans('app.email') }}</th>
        <th>{{ trans('app.shop') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td><img src="{{ get_avatar_src($trash, 'tiny') }}" class="img-circle img-sm admin-table__avatar" alt=""></td>
          <td>{{ $trash->nice_name }}</td>
          <td>{{ $trash->name }}</td>
          <td>{{ $trash->email }}</td>
          <td>
            @if ($trash->owns)
              <div class="admin-table__shop-cell">
                <img src="{{ get_storage_file_url(optional($trash->owns->image)->path, 'tiny') }}" class="img-circle img-sm" alt="">
                <span>{{ $trash->owns->name }}</span>
              </div>
            @endif
          </td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.vendor.shop.restore', $trash->owns->id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.vendor.shop.destroy', $trash->owns->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete_permanently') }}" data-toggle="tooltip">
                <i class="fa fa-trash-o"></i>
              </button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection
