<section>
  <div id="all-categories-wrapper">
    <div class="container">
      <div class="row">
        @foreach (($all_categories ?? []) as $category)
          @if ($category->subCategories->count())
            <div class="col-12 mb-5 pt-4 pb-3 category-grp-wrapper" @if ($category->backgroundImage) style="background-image: url('{{ get_storage_file_url(optional($category->backgroundImage)->path, 'full') }}')" @endif>
              <h2 class="mb-2">
                <a href="{{ route('categories.browse', $category->slug) }}">
                  {{ Str::upper($category->name) }}
                </a>
              </h2>

              <div class="row px-3">
                <div class="col-6 col-md-4 col-lg-3 pl-1 pr-3 my-2">
                  <ul class="nav-category-inner-list show-hide-content less">
                    @foreach ($category->subCategories as $cat)
                      <li>
                        <a href="{{ get_category_url($cat) }}">{{ $cat->name }}</a>
                      </li>
                    @endforeach
                  </ul>

                  @if ($category->subCategories->count() > 3)
                    <a href="javascript::void(0)" class="small show-hide-content-btn">
                      {{ trans('theme.show_more') }} <i class="fa fa-angle-down"></i>
                    </a>
                  @endif
                </div><!-- /.col-3 -->
              </div> <!-- /.row -->
            </div><!-- /.col-12 -->
          @endif
        @endforeach
      </div> <!-- /.row -->
    </div> <!-- /.container -->
  </div> <!-- /#all-categories-wrapper -->
</section>
