@extends('admin.layouts.master')

@section('page_title')
  {{ trans('app.topics') }}
@endsection

@section('content')
  <div class="row admin-faq-layout">
    <div class="col-md-3">
      @include('admin.partials.ui.card_start', [
        'title' => trans('app.topics'),
        'icon' => 'fa-folder-open-o',
        'actions' => view('admin.faq._topics_header_actions')->render(),
        'bodyClass' => 'admin-card__body--compact',
      ])

      <table class="table admin-table admin-table--compact">
        <tbody>
          @foreach ($topics as $topic)
            <tr>
              <td>
                <strong>{{ $topic->name }}</strong>
                <br><span class="label label-outline">{{ $topic->for }}</span>
              </td>
              <td class="row-options admin-row-actions">
                @can('create', \App\Models\Faq::class)
                  <a href="javascript:void(0)" data-link="{{ route('admin.utility.faqTopic.edit', $topic->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
                  {!! Form::open(['route' => ['admin.utility.faqTopic.destroy', $topic->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
                  <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.trash') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
                  {!! Form::close() !!}
                @endcan
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>

      @include('admin.partials.ui.card_end')
    </div>

    <div class="col-md-9">
      @include('admin.partials.ui.card_start', [
        'title' => trans('app.faqs'),
        'icon' => 'fa-question-circle',
        'actions' => view('admin.faq._faqs_header_actions')->render(),
      ])

      <table class="table table-hover admin-table table-no-sort">
        <thead>
          <tr>
            <th>{{ trans('app.detail') }}</th>
            <th>{{ trans('app.topic') }}</th>
            <th>{{ trans('app.updated_at') }}</th>
            <th class="admin-table__actions-col">{{ trans('app.option') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($faqs as $faq)
            <tr>
              <td width="60%">
                @can('update', $faq)
                  <a href="javascript:void(0)" data-link="{{ route('admin.utility.faq.edit', $faq->id) }}" class="ajax-modal-btn"><strong>{!! $faq->question !!}</strong></a>
                @else
                  <strong>{!! $faq->question !!}</strong>
                @endcan
                <br>
                <span class="excerpt-td text-muted">{!! $faq->answer !!}</span>
              </td>
              <td>
                {{ $faq->topic->name }}
                <br><span class="label label-default">{{ strtoupper($faq->topic->for) }}</span>
              </td>
              <td class="small">{{ $faq->updated_at->toFormattedDateString() }}</td>
              <td class="row-options admin-row-actions">
                @can('update', $faq)
                  <a href="javascript:void(0)" data-link="{{ route('admin.utility.faq.edit', $faq->id) }}" class="admin-action-btn ajax-modal-btn" title="{{ trans('app.edit') }}" data-toggle="tooltip"><i class="fa fa-edit"></i></a>
                @endcan
                @can('delete', $faq)
                  {!! Form::open(['route' => ['admin.utility.faq.destroy', $faq->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
                  <button type="submit" class="admin-action-btn confirm ajax-silent" title="{{ trans('app.trash') }}" data-toggle="tooltip"><i class="fa fa-trash-o"></i></button>
                  {!! Form::close() !!}
                @endcan
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>

      @include('admin.partials.ui.card_end')
    </div>
  </div>
@endsection
