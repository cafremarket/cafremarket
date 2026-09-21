<td>
  <h5>
    <a href="{{ route('admin.catalog.category.show', $category->id) }}">{{ $category->name }}</a>
    @if ($category->featured)
      <small class="label label-primary indent10">{{ trans('app.featured') }}</small>
    @endif

    @unless ($category->active)
      <span class="label label-default indent5 small">{{ trans('app.inactive') }}</span>
    @endunless
  </h5>
  @if ($category->description)
    <span class="excerpt-td small">
      {!! Str::limit($category->description, 200) !!}
    </span>
  @endif
</td>
