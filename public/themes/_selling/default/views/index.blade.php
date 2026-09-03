@extends('layouts.main')

@section('content')
  <section id="benefits" class="sf-sell-section">
    <div class="container">
      <div class="sf-sell-section__head">
        <span class="sf-sell-section__eyebrow">{{ trans('theme.selling_page.benefits_eyebrow') }}</span>
        <h2 class="sf-sell-section__title">{{ trans('theme.benefits') }}</h2>
        <p class="sf-sell-section__subtitle">{{ trans('messages.merchant_benefits') }}</p>
      </div>

      <div class="sf-sell-benefits">
        @foreach (['one', 'two', 'three', 'four', 'five', 'six'] as $key)
          <article class="sf-sell-benefit-card">
            <div class="sf-sell-benefit-card__icon">
              <i class="fa fa-{{ trans('theme.benefit.'.$key.'.icon') }}"></i>
            </div>
            <h3 class="sf-sell-benefit-card__title">{{ trans('theme.benefit.'.$key.'.title') }}</h3>
            <p class="sf-sell-benefit-card__text">{{ trans('theme.benefit.'.$key.'.detail') }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section id="howItWorks" class="sf-sell-section sf-sell-section--alt">
    <div class="container">
      <div class="sf-sell-section__head">
        <span class="sf-sell-section__eyebrow">{{ trans('theme.selling_page.steps_eyebrow') }}</span>
        <h2 class="sf-sell-section__title">{{ trans('theme.how_it_works') }}</h2>
        <p class="sf-sell-section__subtitle">{{ trans('messages.how_the_marketplace_works') }}</p>
      </div>

      <div class="sf-sell-steps">
        @foreach (['step_1', 'step_2', 'step_3', 'step_4'] as $step)
          <article class="sf-sell-step">
            <h3 class="sf-sell-step__title">{{ trans('theme.how_it_work_steps.'.$step.'.title') }}</h3>
            <p class="sf-sell-step__text">{!! trans('theme.how_it_work_steps.'.$step.'.detail') !!}</p>
          </article>
        @endforeach
      </div>

      <div class="sf-sell-steps-cta">
        <a href="{{ route('selling.register') }}" class="sf-sell-btn sf-sell-btn--primary sf-sell-btn--lg">
          {{ trans('theme.selling_page.start_selling_now') }}
        </a>
      </div>
    </div>
  </section>

  <section id="pricing" class="sf-sell-section" data-api-section="pricing" style="display: none;">
    <div class="container">
      <div class="sf-sell-section__head">
        <span class="sf-sell-section__eyebrow">{{ trans('theme.selling_page.pricing_eyebrow') }}</span>
        <h2 class="sf-sell-section__title">{{ trans('theme.pricing') }}</h2>
        <p class="sf-sell-section__subtitle">{{ trans('messages.choose_subscription') }}</p>
      </div>

      <div id="sfSellPricingRoot" class="sf-sell-pricing">
        <div class="sf-sell-api-loading">{{ trans('theme.selling_page.loading_plans') }}</div>
      </div>
    </div>
  </section>

  <section id="faqs" class="sf-sell-section sf-sell-section--alt">
    <div class="container">
      <div class="sf-sell-section__head">
        <span class="sf-sell-section__eyebrow">{{ trans('theme.selling_page.faq_eyebrow') }}</span>
        <h2 class="sf-sell-section__title">{{ trans('theme.faq') }}</h2>
        <p class="sf-sell-section__subtitle">{{ trans('messages.faqs') }}</p>
      </div>

      <div id="sfSellFaqsRoot">
        <div class="sf-sell-api-loading">{{ trans('theme.selling_page.loading_faqs') }}</div>
      </div>
    </div>
  </section>
@endsection

@section('scripts')
  <script>
    window.sfSellingApi = {
      config: @json(route('selling.api.config')),
      faqs: @json(route('selling.api.faqs')),
      plans: @json(route('selling.api.subscription_plans')),
      labels: {
        free: @json(__('theme.free')),
        perMonth: @json(__('theme.per_month')),
        choosePlan: @json(__('theme.button.choose_plan')),
        planTagline: @json(trans('theme.selling_page.plan_default_tagline')),
        noFaqs: @json(trans('theme.selling_page.no_faqs')),
        loadError: @json(trans('theme.selling_page.api_load_error')),
        heroPricingNote: @json(trans('theme.selling_page.hero_pricing_note', ['price' => ':price'])),
        heroFreeNote: @json(trans('theme.selling_page.hero_free_note')),
      }
    };
  </script>
  <script src="{{ selling_theme_asset_url('js/selling-api.js') }}?v={{ @filemtime(selling_theme_assets_path().'/js/selling-api.js') ?: time() }}"></script>
@endsection
