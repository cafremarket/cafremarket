{{-- Mass-action checkbox column header. Pass: model class for @can --}}
@can('massDelete', $model)
  <th class="massActionWrapper admin-table__check-col">
    <div class="btn-group admin-mass-actions">
      <button type="button" class="btn btn-xs btn-default checkbox-toggle" title="{{ trans('app.select_all') }}">
        <i class="fa fa-square-o"></i>
      </button>
      <button type="button" class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">
        <span class="caret"></span>
      </button>
      <ul class="dropdown-menu" role="menu">
        @foreach ($massActions ?? [] as $action)
          <li>
            <a href="javascript:void(0)" data-link="{{ $action['url'] }}" class="massAction" data-doafter="{{ $action['after'] ?? 'reload' }}">
              <i class="fa {{ $action['icon'] ?? 'fa-trash' }}"></i> {{ $action['label'] }}
            </a>
          </li>
        @endforeach
      </ul>
    </div>
  </th>
@endcan
