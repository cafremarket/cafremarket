<div class="container">
  <header class="page-header mt-3">
    <div class="row">
      <div class="col-md-12">
        <ol class="breadcrumb nav-breadcrumb">
          @include('theme::headers.lists.home')
          @include('theme::headers.lists.categories')
          <li class="active">{{ $category->name }}</li>
        </ol>
        @if (!empty($category->subCategories) && $category->subCategories->count())
          <div class="sf-category-children" style="margin: 16px 0 8px;">
            <div class="row">
              @foreach ($category->subCategories as $subCategory)
                <div class="col-xs-6 col-sm-3 col-md-2" style="margin-bottom: 12px;">
                  <a href="{{ get_category_url($subCategory) }}" class="btn btn-default btn-block" style="white-space: normal;">
                    {{ $subCategory->name }}
                  </a>
                </div>
              @endforeach
            </div>
          </div>
        @endif
      </div>
    </div>
  </header>
</div>
