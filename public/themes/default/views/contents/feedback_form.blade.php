<section class="sf-detail-page">
  <div class="container mb-3">
    <div class="sf-feedback-layout">
      <div>
        <div class="sf-feedback-card">
          <div class="sf-feedback-card__hint">
            <strong>@lang('theme.why_feedback_important')</strong>
            <p class="mb-0 mt-1">@lang('theme.help.be_honest_when_leave_feedbacks')</p>
          </div>

          <h3 class="sf-account-section__title mb-3">@lang('theme.section_headings.give_feedbacks_to_seller')</h3>

          <div class="sf-order-item mb-3" style="border:1px solid #eef2f7;border-radius:12px;padding:14px;">
            <div class="product-img-wrap">
              @include('theme::partials._shop_logo_frame', ['shop' => $order->shop, 'frameSize' => 'sm', 'thumbSize' => 'small', 'fullSize' => 'small'])
            </div>
            <div class="sf-order-item__info">
              <a href="{{ route('show.store', $order->shop->slug) }}" class="sf-wishlist-item__title">
                {{ $order->shop->name }}
              </a>
              <div class="mt-2">
                @if (optional($order->shop->avgFeedback)->rating)
                  @include('theme::layouts.ratings', ['ratings' => $order->shop->avgFeedback->rating, 'count' => $order->shop->avgFeedback->count])
                @else
                  <span class="text-muted small">@lang('theme.no_reviews')</span>
                @endif
              </div>
            </div>
          </div>

          <p class="mb-2">@lang('theme.how_satisfied_you_are')</p>

          <div class="post-review-box">
            @if ($order->feedback)
              @include('theme::layouts.ratings', ['ratings' => $order->feedback->rating])
              <p>
                {{ $order->feedback->comment != '' ? $order->feedback->comment : trans('theme.no_comment') }}
              </p>
            @else
              {!! Form::open(['route' => ['shop.feedback', $order], 'files' => true, 'class' => 'sf-form', 'data-toggle' => 'validator']) !!}
              <div class="product-info-rating feedback-stars mb-3">
                <span class="star rated" data-toggle="tooltip" data-title="@lang('theme.hate_it')" data-value="1">
                  <i class="fas fa-star fa-fw"></i>
                </span>
                <span class="star rated" data-toggle="tooltip" data-title="@lang('theme.not_so_good')" data-value="2">
                  <i class="fas fa-star fa-fw"></i>
                </span>
                <span class="star rated" data-toggle="tooltip" data-title="@lang('theme.its_ok')" data-value="3">
                  <i class="fas fa-star fa-fw"></i>
                </span>
                <span class="star rated" data-toggle="tooltip" data-title="@lang('theme.like_it')" data-value="4">
                  <i class="fas fa-star fa-fw"></i>
                </span>
                <span class="star rated" data-toggle="tooltip" data-title="@lang('theme.love_it')" data-value="5">
                  <i class="fas fa-star fa-fw"></i>
                </span>
                <span class="response small text-primary">@lang('theme.love_it')</span>
                {{ Form::hidden('rating', 5, ['class' => 'rating-value']) }}
              </div>

              <div class="sf-form-group">
                {{ Form::textarea('comment', null, ['rows' => '2', 'class' => 'form-control sf-input', 'placeholder' => trans('theme.placeholder.write_your_feedback'), 'minlength' => '10', 'maxlength' => '250']) }}
                <div class="help-block with-errors"></div>
              </div>

              <button class="confirm btn sf-btn-primary" data-confirm="@lang('theme.confirm_action.cant_undo')" type="submit">@lang('theme.button.save')</button>
              {!! Form::close() !!}
            @endif
          </div>
        </div>
      </div>

      <div>
        <div class="sf-feedback-card">
          <h3 class="sf-account-section__title mb-3">@lang('theme.section_headings.give_feedbacks_to_products')</h3>

          {!! Form::open(['route' => ['save.feedback', $order], 'files' => true, 'id' => 'form', 'class' => 'sf-form', 'data-toggle' => 'validator']) !!}
          @php
            $feedback_given = true;
          @endphp

          @foreach ($order->inventories as $item)
            <div class="sf-feedback-product">
              <img src="{{ get_inventory_img_src($item, 'small') }}" alt="{{ $item->slug }}" title="{{ $item->slug }}" />

              <div style="flex:1;min-width:0;">
                <a href="{{ storefront_product_url($item) }}" class="sf-wishlist-item__title" style="font-size:0.95rem;">
                  {{ $item->pivot->item_description }}
                </a>
                <div class="mt-1 mb-2">
                  @if (optional($item->avgFeedback)->rating)
                    @include('theme::layouts.ratings', ['ratings' => $item->avgFeedback->rating, 'count' => $item->avgFeedback->count])
                  @else
                    <span class="text-muted small">@lang('theme.no_reviews')</span>
                  @endif
                </div>

                @if ($item->pivot->feedback_id)
                  @php
                    $feedback = \App\Models\Feedback::find($item->pivot->feedback_id);
                  @endphp

                  @include('theme::layouts.ratings', ['ratings' => $feedback->rating])
                  <p class="mb-0">
                    {{ $feedback->comment != '' ? $feedback->comment : trans('theme.no_comment') }}
                  </p>
                @else
                  @php
                    $feedback_given = false;
                  @endphp

                  <div class="post-review-box">
                    <div class="product-info-rating feedback-stars mb-2">
                      <span class="star rated" data-toggle="tooltip" data-title="@lang('theme.hate_it')" data-value="1">
                        <i class="fas fa-star fa-fw"></i>
                      </span>
                      <span class="star rated" data-toggle="tooltip" data-title="@lang('theme.not_so_good')" data-value="2">
                        <i class="fas fa-star fa-fw"></i>
                      </span>
                      <span class="star rated" data-toggle="tooltip" data-title="@lang('theme.its_ok')" data-value="3">
                        <i class="fas fa-star fa-fw"></i>
                      </span>
                      <span class="star rated" data-toggle="tooltip" data-title="@lang('theme.like_it')" data-value="4">
                        <i class="fas fa-star fa-fw"></i>
                      </span>
                      <span class="star rated" data-toggle="tooltip" data-title="@lang('theme.love_it')" data-value="5">
                        <i class="fas fa-star fa-fw"></i>
                      </span>
                      <span class="response small text-primary">@lang('theme.love_it')</span>
                      {{ Form::hidden('items[' . $item->pivot->inventory_id . '][rating]', 5, ['class' => 'rating-value']) }}
                    </div>

                    <div class="sf-form-group mb-0">
                      {{ Form::textarea('items[' . $item->pivot->inventory_id . '][comment]', null, ['rows' => '2', 'class' => 'form-control sf-input', 'placeholder' => trans('theme.placeholder.write_your_feedback'), 'minlength' => 10, 'maxlength' => 250]) }}
                      <div class="help-block with-errors"></div>
                    </div>
                  </div>
                @endif
              </div>
            </div>
          @endforeach

          <div class="sf-form-actions mt-3">
            @if (!$feedback_given && $order->inventories->count() > 1)
              <p class="text-muted text-info small mb-0"><i class="fas fa-info-circle"></i> @lang('theme.help.give_all_feedbacks_together')</p>
            @else
              <span></span>
            @endif

            @if ($feedback_given)
              <p class="text-muted mb-0">@lang('theme.notify.your_feedback_saved')</p>
            @else
              <button class="confirm btn sf-btn-primary" data-confirm="@lang('theme.confirm_action.cant_undo')" type="submit">@lang('theme.button.save')</button>
            @endif
          </div>
          {!! Form::close() !!}
        </div>
      </div>
    </div>
  </div>
</section>
