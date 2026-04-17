@if (config('system_settings.publicly_show_affiliate_commission'))
  <div class="row mt-3 affiliate-section">
    <div class="col-md-8 text-left">
      <p> {{ trans('packages.affiliate.affiliate_commission') }} : {{ $item->affiliate_commission_percentage_text }} </p>
    </div>

    <div class="col-md-4">
      <div class="affiliate-button text-right">
        <!-- Action Buttons -->
        @if ($item->myLink)
          <a href="{{ route('affiliate.link.index') }}" class="text-muted">
            {{ trans('packages.affiliate.in_your_portfolio') }}
          </a>
        @else
          @guest('affiliate')
            <a href="{{ route('affiliate.login.form') }}" class="btn btn-sm btn-primary">
              {{ trans('packages.affiliate.start_earning') }}
            </a>
          @endguest

          @auth('affiliate')
            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#createAffiliateModal">
              {{ trans('packages.affiliate.create_your_link') }}
            </button>
          @endauth
        @endif
      </div>
    </div> <!-- /.col-* -->
  </div> <!-- /.row -->
  <hr class="dotted mb-4" />

  <!-- Edit Modal -->
  @auth('affiliate')
    @unless ($item->myLink)
      @include('affiliate::frontend._create_link_modal', ['item' => $item])
    @endunless
  @endauth
@endif
