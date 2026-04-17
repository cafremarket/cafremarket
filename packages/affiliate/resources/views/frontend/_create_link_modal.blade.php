<!-- Create Modal -->
<div class="modal fade" id="createAffiliateModal" tabindex="-1" role="dialog" aria-labelledby="createAffiliateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createAffiliateModalLabel">{{ trans('packages.affiliate.create_affiliate_link') }}</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body pt-0">
        {!! Form::open(['route' => ['affiliate.link.store', $item]]) !!}

        @include('affiliate::_affiliate_link_form')

        <div class="form-group col-md-12 mb-4">
          {!! Form::submit(trans('packages.affiliate.create_affiliate_link'), ['class' => 'btn btn-primary']) !!}
        </div>

        {!! Form::close() !!}
      </div>
      <div class="modal-footer">
      </div>
    </div>
  </div>
</div>
