@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.subscription_plans') }}
@endsection

@section('content')
  @php
    $planModel = \App\Models\SubscriptionPlan::class;
    $massActions = [
      ['url' => route('admin.setting.subscriptionPlan.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.setting.subscriptionPlan.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.subscription_plans'),
    'icon' => 'fa-credit-card',
    'actions' => view('admin.subscription_plan._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-no-option" id="sortable" data-action="{{ Route('admin.setting.subscriptionPlan.reorder') }}">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $planModel, 'massActions' => $massActions])
        @cannot('massDelete', $planModel)
          {{-- no mass column --}}
        @endcannot
        <th width="7px">{{ trans('app.#') }}</th>
        <th>{{ trans('app.name') }}</th>
        <th><i class="fa fa-money"></i> {{ trans('app.cost_per_month') }}</th>
        <th><i class="fa fa-users"></i> {{ trans('app.team_size') }}</th>
        <th><i class="fa fa-cubes"></i> {{ trans('app.inventory_limit') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($subscription_plans as $subscriptionPlan)
        <tr id="{{ $subscriptionPlan->plan_id }}">
          @can('massDelete', $planModel)
            <td>
              @if ($subscriptionPlan->shops_count)
                <i class="fa fa-ban text-muted" data-toggle="tooltip" title="{{ trans('help.this_plan_has_active_subscribers') }}"></i>
              @else
                <input id="{{ $subscriptionPlan->plan_id }}" type="checkbox" class="massCheck">
              @endif
            </td>
          @endcan
          <td><i class="fa fa-arrows sort-handler admin-table__sort-handle" data-toggle="tooltip" title="{{ trans('app.move') }}"></i></td>
          <td>
            {{ $subscriptionPlan->name }}
            @if ($subscriptionPlan->featured)
              <span class="label label-primary">{{ trans('app.featured') }}</span>
            @endif
            <span class="label label-outline" data-toggle="tooltip" title="{{ trans('help.subscribers_count') }}">{{ $subscriptionPlan->shops_count }}</span>
          </td>
          <td>{{ get_formated_currency($subscriptionPlan->cost, 2, config('system_settings.currency.id')) }}</td>
          <td>{{ $subscriptionPlan->team_size }}</td>
          <td>{{ $subscriptionPlan->inventory_limit }}</td>
          <td class="row-options admin-row-actions">
            @can('view', $subscriptionPlan)
              <a href="javascript:void(0)" data-link="{{ route('admin.setting.subscriptionPlan.show', $subscriptionPlan->plan_id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.detail') }}" data-toggle="tooltip"><i class="fa fa-expand"></i></a>
            @endcan
            @can('update', $subscriptionPlan)
              <a href="javascript:void(0)" data-link="{{ route('admin.setting.subscriptionPlan.edit', $subscriptionPlan->plan_id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
            @can('delete', $subscriptionPlan)
              @if ($subscriptionPlan->shops_count)
                <i class="fa fa-trash-o text-muted" data-toggle="tooltip" title="{{ trans('help.this_plan_has_active_subscribers') }}"></i>
              @else
                {!! Form::open(['route' => ['admin.setting.subscriptionPlan.trash', $subscriptionPlan->plan_id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
                <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.trash') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
                {!! Form::close() !!}
              @endif
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')

  @include('admin.partials.ui.trash_start', ['title' => trans('app.trash')])

  <table class="table table-hover admin-table table-no-option">
    <thead>
      <tr>
        <th>{{ trans('app.name') }}</th>
        <th>{{ trans('app.cost_per_month') }}</th>
        <th>{{ trans('app.team_size') }}</th>
        <th>{{ trans('app.inventory_limit') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td>{{ $trash->name }}</td>
          <td>{{ get_formated_currency($trash->cost, 2, config('system_settings.currency.id')) }}</td>
          <td>{{ $trash->team_size }}</td>
          <td>{{ $trash->inventory_limit }}</td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.setting.subscriptionPlan.restore', $trash->plan_id), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.setting.subscriptionPlan.destroy', $trash->plan_id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
              <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.delete_permanently') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
              {!! Form::close() !!}
            @endcan
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>

  @include('admin.partials.ui.card_end')
@endsection

@section('page-script')
  @include('plugins.drag-n-drop')
@endsection
