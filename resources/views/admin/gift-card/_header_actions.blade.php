@can('create', \App\Models\GiftCard::class)
  <a href="javascript:void(0)" data-link="{{ route('admin.promotion.giftCard.create') }}" class="ajax-modal-btn btn btn-new btn-flat">
    <i class="fa fa-plus"></i> {{ trans('app.add_gift_card') }}
  </a>
@endcan
