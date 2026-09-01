@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.languages') }}
@endsection

@section('content')
  @php
    $languageModel = \App\Models\Language::class;
    $massActions = [
      ['url' => route('admin.setting.language.massTrash'), 'label' => trans('app.trash'), 'icon' => 'fa-trash'],
      ['url' => route('admin.setting.language.massDestroy'), 'label' => trans('app.delete_permanently'), 'icon' => 'fa-times'],
    ];
  @endphp

  @include('admin.partials.ui.card_start', [
    'title' => trans('app.languages'),
    'icon' => 'fa-language',
    'actions' => view('admin.language._header_actions')->render(),
  ])

  <table class="table table-hover admin-table table-2nd-no-sort">
    <thead>
      <tr>
        @include('admin.partials.ui.mass_checkbox_header', ['model' => $languageModel, 'massActions' => $massActions])
        @cannot('massDelete', $languageModel)
          {{-- no mass column --}}
        @endcannot
        <th>{{ trans('app.language') }}</th>
        <th>{{ trans('app.code') }}</th>
        <th>{{ trans('app.php_locale_code') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody id="massSelectArea">
      @foreach ($languages as $language)
        <tr>
          @can('massDelete', $languageModel)
            <td><input id="{{ $language->id }}" type="checkbox" class="massCheck"></td>
          @endcan
          <td width="45%">
            <div class="admin-table__shop-cell">
              <img src="{{ asset(sys_image_path('flags') . array_slice(explode('_', $language->php_locale_code), -1)[0] . '.png') }}" class="lang-flag small" alt="">
              <div>
                {{ $language->language }}
                @if ($language->rtl)
                  <span class="label label-outline">{{ trans('app.rtl') }}</span>
                @endif
                @if ($language->active)
                  <span class="label label-primary">{{ trans('app.active') }}</span>
                @endif
              </div>
            </div>
          </td>
          <td>{!! $language->code !!}</td>
          <td>{!! $language->php_locale_code !!}</td>
          <td class="row-options admin-row-actions">
            @can('update', $language)
              <a href="javascript:void(0)" data-link="{{ route('admin.setting.language.edit', $language) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
            @endcan
            @can('delete', $language)
              @if (in_array($language->code, ['en', 'pt'], true) || in_array($language->id, config('system.freeze.languages')))
                <i class="fa fa-bell-o text-muted" data-toggle="tooltip" title="{{ trans('messages.freezed_model') }}"></i>
              @else
                {!! Form::open(['route' => ['admin.setting.language.trash', $language], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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

  <table class="table table-hover admin-table table-2nd-sort">
    <thead>
      <tr>
        <th>{{ trans('app.language') }}</th>
        <th>{{ trans('app.code') }}</th>
        <th>{{ trans('app.deleted_at') }}</th>
        <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($trashes as $trash)
        <tr>
          <td width="45%">
            <img src="{{ asset(sys_image_path('flags') . array_slice(explode('_', $trash->php_locale_code), -1)[0] . '.png') }}" class="lang-flag small" alt="">
            <span>{{ $trash->language }}</span>
            @if ($trash->rtl)
              <span class="label label-outline">{{ trans('app.rtl') }}</span>
            @endif
          </td>
          <td>{!! $trash->code !!}</td>
          <td>{{ $trash->deleted_at->diffForHumans() }}</td>
          <td class="row-options admin-row-actions">
            @can('delete', $trash)
              @include('admin.partials.ui.action_btn', ['href' => route('admin.setting.language.restore', $trash), 'icon' => 'fa-database', 'title' => trans('app.restore')])
              {!! Form::open(['route' => ['admin.setting.language.destroy', $trash], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
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
