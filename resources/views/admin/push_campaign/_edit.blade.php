<div class="modal-dialog modal-lg">
  <div class="modal-content">
    {!! Form::model($campaign, ['route' => ['admin.promotion.push_campaign.update', $campaign], 'method' => 'PUT', 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h4 class="modal-title">Edit push campaign</h4>
    </div>
    <div class="modal-body">
      @include('admin.push_campaign._form')
    </div>
    <div class="modal-footer">
      {!! Form::submit('Update draft', ['class' => 'btn btn-flat btn-default']) !!}
      <button type="submit" name="send_now" value="1" class="btn btn-flat btn-primary confirm">
        <i class="fa fa-paper-plane"></i> Update &amp; send now
      </button>
    </div>
    {!! Form::close() !!}
  </div>
</div>
