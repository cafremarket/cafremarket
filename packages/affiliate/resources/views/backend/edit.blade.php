<!-- a template for each modal -->
<div class="modal-dialog modal-lg">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="false">&times;</button>
      {{ trans('packages.affiliate.edit_affiliate_link') }} <!--Insert Modal Header Here -->
    </div>
    <div class="modal-body">
      {{ Form::model($link, ['route' => ['affiliate.link.update', $link], 'method' => 'put']) }}

      @include('affiliate::_affiliate_link_form')

      <div class="form-group col-md-12 mb-4">
        {!! Form::submit(trans('packages.affiliate.update_affiliate_link'), ['class' => 'btn btn-primary']) !!}
      </div>

      {{ Form::close() }}
    </div>
    <div class="modal-footer">
    </div>
  </div> <!-- / .modal-content -->
</div> <!-- / .modal-dialog -->
