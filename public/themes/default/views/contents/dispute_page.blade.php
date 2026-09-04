<section class="sf-detail-page">
  <div class="container mb-5">
    <div class="sf-dispute-layout">
      <aside class="sf-dispute-help">
        <p class="lead">{!! trans('theme.section_headings.how_to_open_a_dispute') !!}</p>
        <h4>{!! trans('theme.help.first_step') !!}:</h4>
        <p>{!! trans('theme.help.how_to_open_a_dispute_first_step') !!}</p>

        <h4>{!! trans('theme.help.second_step') !!}:</h4>
        <p>{!! trans('theme.help.how_to_open_a_dispute_second_step') !!}</p>

        <h4>{!! trans('theme.help.third_step') !!}:</h4>
        <p>{!! trans('theme.help.how_to_open_a_dispute_third_step') !!}</p>
      </aside>

      <div>
        @php
          $progress = $order->dispute ? $order->dispute->progress() : 0;
        @endphp
        <div class="sf-panel mb-3">
          <div class="sf-panel__body" style="padding:18px;">
            <div class="step-wizard-wrapper">
              <div class="step-wizard">
                <div class="progress">
                  <div class="progressbar empty"></div>
                  <div id="prog" class="progressbar" style="width: {{ $progress }}%;"></div>
                </div>
                <ul>
                  <li class="{{ $progress > 33 ? 'done' : 'active' }}">
                    <a id="step1">
                      <span class="step">1</span>
                      <span class="title">{!! trans('theme.open_a_dispute') !!}</span>
                    </a>
                  </li>
                  <li class="{{ $progress > 66 ? 'done' : ($progress > 33 ? 'active' : '') }}">
                    <a id="step2">
                      <span class="step">2</span>
                      <span class="title">{!! trans('theme.seller_helps_you') !!}</span>
                    </a>
                  </li>
                  <li class="{{ $progress == 100 ? 'done' : ($progress > 66 ? 'active' : '') }}">
                    <a id="step3">
                      <span class="step">3</span>
                      <span class="title">{!! trans('theme.marketplace_steps_in', ['marketplace' => get_platform_title()]) !!}<br />
                        <i class="small hidden-xs">{!! trans('theme.help.when_marketplace_steps_in') !!}</i>
                      </span>
                    </a>
                  </li>
                  <li class="{{ $progress == 100 ? 'done' : '' }}">
                    <a id="step4">
                      <span class="step">4</span>
                      <span class="title">{!! trans('theme.dispute_finished') !!}</span>
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        @if ($order->dispute)
          <div class="sf-panel">
            <div class="sf-panel__head">{!! trans('theme.dispute_detail') !!}</div>
            <div class="sf-panel__body" style="padding:0;">
              <div class="sf-detail-meta-grid">
                <div class="sf-detail-meta-grid__item">
                  <span>{!! trans('theme.store') !!}</span>
                  <div>
                    @if ($order->shop->slug)
                      <a href="{{ route('show.store', $order->shop->slug) }}">{{ $order->shop->name }}</a>
                    @else
                      {!! trans('theme.seller') !!}
                    @endif
                  </div>
                  <div class="mt-2">{!! $order->dispute->statusName() !!}</div>
                </div>
                <div class="sf-detail-meta-grid__item">
                  <span>{!! trans('theme.refund_amount') !!}</span>
                  <strong>{{ get_formated_currency($order->dispute->refund_amount, 2, $order->currency_id) }}</strong>
                  <div class="mt-2">
                    <span>{!! trans('theme.return_goods') !!}:</span>
                    {{ $order->dispute->return_goods == 1 ? trans('theme.yes') : trans('theme.no') }}
                  </div>
                </div>
                <div class="sf-detail-meta-grid__item">
                  <span>{!! trans('theme.order_id') !!}</span>
                  <strong><a href="{{ route('order.detail', $order) }}">{{ $order->order_number }}</a></strong>
                  <div class="mt-2">
                    <span>{!! trans('theme.order_received') !!}:</span>
                    {{ $order->dispute->order_received == 1 ? trans('theme.yes') : trans('theme.no') }}
                  </div>
                </div>
              </div>

              <div style="padding:16px 18px;border-top:1px solid #eef2f7;">
                <p class="lead mb-3">
                  <strong>{!! trans('theme.reason') !!}:</strong>
                  {{ $order->dispute->dispute_type->detail }}
                </p>

                @if ($order->dispute->description)
                  <div class="mb-4">
                    {{ $order->dispute->description }}
                    @if (count($order->dispute->attachments))
                      <small class="pull-right">
                        {{ trans('app.attachments') . ': ' }}
                        @foreach ($order->dispute->attachments as $attachment)
                          <a href="{{ route('attachment.download', $attachment->path) }}"><i class="fas fa-file"></i></a>
                        @endforeach
                      </small>
                    @endif
                  </div>
                @endif

                @if ($order->dispute->replies->count() > 0)
                  <div class="sf-message-thread mb-3">
                    @foreach ($order->dispute->replies as $reply)
                      <div class="sf-message-bubble {{ $reply->customer_id ? 'sf-message-bubble--me' : '' }}">
                        <div>
                          <div class="sf-message-bubble__meta">
                            <strong>
                              @if ($reply->user_id)
                                {{ $reply->user->getName() }}
                              @elseif ($reply->customer_id)
                                {{ $reply->customer->getName() }}
                              @endif
                            </strong>
                            {{ $reply->updated_at->diffForHumans() }}
                          </div>
                          <div class="sf-message-bubble__body">
                            {{ $reply->reply }}
                            @if (count($reply->attachments))
                              <div class="sf-message-bubble__attach">
                                @foreach ($reply->attachments as $attachment)
                                  <a href="{{ route('attachment.download', $attachment) }}" class="btn btn-default btn-xs">
                                    <i class="fas fa-file"></i>
                                  </a>
                                @endforeach
                              </div>
                            @endif
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                @endif

                <div class="text-center my-3">
                  @if ($order->dispute->isClosed())
                    <a class="btn btn-danger" href="javascript:void(0);" data-toggle="modal" data-target="#disputeAppealModal">{!! trans('theme.button.appeal') !!}</a>
                  @else
                    <a class="btn btn-default" href="javascript:void(0);" data-toggle="modal" data-target="#disputeResponseModal">{!! trans('theme.button.response') !!}</a>

                    {!! Form::open(['route' => ['dispute.markAsSolved', $order->dispute], 'class' => 'form-btn d-inline-block']) !!}
                    {!! Form::button(trans('theme.mark_as_solved'), ['type' => 'submit', 'class' => 'confirm btn sf-btn-primary flat']) !!}
                    {!! Form::close() !!}
                  @endif
                </div>
              </div>
            </div>
          </div>
        @else
          <div class="sf-panel">
            <div class="sf-panel__body" style="padding:24px;text-align:center;">
              <a href="{{ route('order.detail', $order) . '#message-section' }}" class="btn sf-btn-primary">{!! trans('theme.button.contact_seller') !!}</a>

              @unless ($order->dispute)
                <a href="javascript:void(0);" data-toggle="modal" data-target="#disputeOpenModal" class="btn btn-default">{!! trans('theme.button.open_dispute') !!}</a>
              @endunless

              <hr />

              <h4 class="pb-2">{!! trans('theme.button.refund_request') !!}:</h4>
              <p class="text-muted pb-4">{!! trans('theme.help.reason_to_refund_request') !!}</p>

              <h4 class="pb-2">{!! trans('theme.button.return_goods') !!}:</h4>
              <p class="text-muted">{!! trans('theme.help.reason_to_return_goods') !!}</p>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</section>
