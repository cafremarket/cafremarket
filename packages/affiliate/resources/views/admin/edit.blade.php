<!-- a template for each modal -->
<div class="modal-dialog modal-md">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="false">×</button>
      {{ trans('packages.affiliate.edit_affiliate') }} <!--Insert Modal Header Here -->
    </div>
    <div class="modal-body">
      {!! Form::model($affiliate, ['route' => ['admin.affiliate.update', $affiliate], 'method' => 'put', 'id' => 'form', 'data-toggle' => 'validator', 'files' => true]) !!}

      @include('affiliate::admin._form')

      <div class="modal-footer">
        {!! Form::submit(trans('app.form.save'), ['class' => 'btn btn-primary']) !!}
        {!! Form::close() !!}
      </div>
    </div>
  </div> <!-- / .modal-content -->
</div> <!-- / .modal-dialog -->