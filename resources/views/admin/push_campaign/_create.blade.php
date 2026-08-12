<div class="modal-dialog modal-lg">
  <div class="modal-content">
    {!! Form::open(['route' => 'admin.promotion.push_campaign.store', 'method' => 'POST', 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h4 class="modal-title">New push campaign</h4>
    </div>
    <div class="modal-body">
      @include('admin.push_campaign._form')
    </div>
    <div class="modal-footer">
      {!! Form::submit('Save draft', ['name' => 'save', 'class' => 'btn btn-flat btn-default']) !!}
      <button type="submit" name="send_now" value="1" class="btn btn-flat btn-primary confirm">
        <i class="fa fa-paper-plane"></i> Save &amp; send now
      </button>
    </div>
    {!! Form::close() !!}
  </div>
</div>
