<td class="row-options admin-row-actions">
  <a href="{{ route('admin.catalog.category.show', $category->id) }}" class="admin-action-btn" data-toggle="tooltip" data-placement="top" title="{{ trans('app.manage_subcategories') }}">
    <i class="fa fa-folder-open"></i>
  </a>

  @can('update', $category)
    <a href="javascript:void(0)" data-link="{{ route('admin.catalog.category.edit', $category->id) }}" class="admin-action-btn ajax-modal-btn" data-toggle="tooltip" data-placement="top" title="{{ trans('app.edit') }}">
      <i class="fa fa-edit"></i>
    </a>
    <a href="{{ route('admin.catalog.category.translate.form', ['category' => $category, 'language' => app()->getLocale()]) }}" class="admin-action-btn" data-toggle="tooltip" data-placement="top" title="{{ trans('app.manage_translations') }}">
      <i class="fa fa-language"></i>
    </a>
  @endcan

  @can('delete', $category)
    {!! Form::open(['route' => ['admin.catalog.category.trash', $category->id], 'method' => 'delete', 'class' => 'data-form admin-inline-form']) !!}
    {!! Form::button('<i class="fa fa-trash-o"></i>', ['type' => 'submit', 'class' => 'admin-action-btn confirm ajax-silent', 'title' => trans('app.trash'), 'data-toggle' => 'tooltip', 'data-placement' => 'top']) !!}
    {!! Form::close() !!}
  @endcan
</td>
