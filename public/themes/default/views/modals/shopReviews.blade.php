<div class="modal fade" id="shopReviewsModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-body p-0">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true" style="position: absolute; top: 5px; right: 10px; z-index: 9; color: #eee;">&times;</button>
        <div class="box-widget widget-shop">
          <div class="widget-shop-header" style="background-image:url( {{ get_cover_img_src($shop, 'shop') }} );">
            <h2 class="widget-shop-name">
              {!! $shop->getQualifiedName() !!}
            </h2>

            <p class="member-since small">
              {{ trans('theme.member_since') }}: {{ $shop->created_at->diffForHumans() }}
            </p>
          </div> <!-- /.widget-shop-header -->

          <div class="widget-shop-image">
            @include('theme::partials._shop_logo_frame', ['shop' => $shop, 'frameSize' => 'sm', 'thumbSize' => 'small', 'fullSize' => 'small'])
          </div>

          <div class="row">
            <div class="col-sm-4 border-right">
              <div class="description-block">
                <h5 class="description-header">{{ $shop->inventories_count }}</h5>
                <span class="description-text">{{ trans('theme.active_listings') }}</span>
              </div>
            </div>

            <div class="col-sm-4 border-right">
              <div class="description-block">
                <h5 class="description-header">&nbsp;</h5>

                <span class="description-text small">
                  @include('theme::layouts.ratings', ['ratings' => $shop->reviews->avg('rating'), 'count' => $shop->reviews->count()])
                </span>
              </div>
            </div>

            <div class="col-sm-4">
              <div class="description-block">
                <h5 class="description-header">{{ $shop->total_item_sold }}</h5>
                <span class="description-text">{{ trans('theme.items_sold') }}</span>
              </div>
            </div>
          </div> <!-- /.row -->
        </div> <!-- /.widget-shop -->

        <!-- Custom Tabs -->
        <div class="nav-tabs-custom">
          <ul class="nav nav-tabs ml-4" role="tablist">
            <li class="active">
              <a href="#description_tab" data-toggle="tab">
                {{ trans('theme.description') }}
              </a>
            </li>

            <li>
              <a href="#merchant_tab" data-toggle="tab">
                {{ trans('theme.profile') }}
              </a>
            </li>

            @if ($shop->config->return_refund)
              <li>
                <a href="#refund_policy_tab" data-toggle="tab">
                  {{ trans('theme.return_and_refund_policy') }}
                </a>
              </li>
            @endif

            <li>
              <a href="#shop_reviews_tab" data-toggle="tab">
                {{ trans('theme.latest_reviews') }}
              </a>
            </li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane active" id="description_tab">
              {!! clean_rich_html($shop->description) !!}
            </div> <!-- /.tab-pane -->

            <div class="tab-pane" id="merchant_tab">
              <div class="row">
                <div class="col-sm-3">
                  <img src="{{ get_avatar_src($shop->owner, 'logo_square') }}" class="img-rounded">
                </div>
                <div class="col-sm-9">
                  {{ $shop->owner->name }}<br />
                  {{ $shop->address->toShortString() }}<br />
                  {{ $shop->config->support_phone }}<br />
                  {{ $shop->config->support_email }}<br />
                </div>
              </div> <!-- /.row -->
            </div> <!-- /.tab-pane -->

            <div class="tab-pane" id="refund_policy_tab">
              {!! $shop->config->return_refund !!}
            </div> <!-- /.tab-pane -->

            <div class="tab-pane" id="shop_reviews_tab">
              @forelse($shop->latestReviews as $review)
                <p>
                  <b>{{ $review->customer->nice_name ?? $review->customer->name }}</b>

                  <span class="pull-right small">
                    <b class="text-success">@lang('theme.verified_purchase')</b>
                    <span class="text-muted"> | {{ $review->created_at->diffForHumans() }}</span>
                  </span>
                </p>

                <p>{{ $review->comment }}</p>

                @include('theme::layouts.ratings', ['ratings' => $review->rating, 'count' => $review->ratings_count])

                @if ($review->hasReply())
                  <div class="small" style="margin-top:4px;padding:8px 10px;background:#f7f7f8;border-left:3px solid #ccc;border-radius:4px;">
                    <strong>@lang('theme.seller_reply')</strong>
                    <p class="mb-0">{{ $review->reply }}</p>
                  </div>
                @endif

                @unless ($loop->last)
                  <hr />
                @endunless
              @empty
                <p class="lead text-center text-muted mt-3">@lang('theme.no_reviews')</p>
              @endforelse
            </div> <!-- /.tab-pane -->
          </div> <!-- /.tab-content -->
        </div> <!-- /.nav-tabs-custom -->
      </div><!-- /.modal-body -->
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog modal-lg -->
</div><!-- /#shopReviewsModal -->
