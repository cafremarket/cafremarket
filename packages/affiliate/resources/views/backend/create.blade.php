<!-- a template for each modal -->
<div class="modal-dialog modal-lg">
  <div class="modal-content">
    <div class="modal-header mb-0">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="false">×</button>
      {{ trans('packages.affiliate.create_affiliate_link') }} <!--Insert Modal Header Here -->
    </div>
    <div class="modal-body">
      {{ Form::open(['route' => 'affiliate.link.store', 'method' => 'post']) }}

      @include('affiliate::frontend.affiliate_link.form')

      <div class="form-group col-md-12 mb-4">
        {!! Form::submit(trans('packages.affiliate.create_affiliate_link'), ['class' => 'btn btn-primary']) !!}
      </div>

      {{ Form::close() }}
    </div>
    <div class="modal-footer">
    </div>
  </div> <!-- / .modal-content -->
</div> <!-- / .modal-dialog -->
