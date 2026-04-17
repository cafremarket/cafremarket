<div class="modal-dialog modal-md">
  <div class="modal-content">
    {!! Form::open(['route' => 'admin.vendor.merchant.upload', 'files' => true, 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.form.upload_csv') }}
    </div>

    <div class="modal-body">
      <ul>
        <li>{!! trans('help.upload_rows', ['rows' => get_csv_import_limit()]) !!}</li>
        <li>{!! trans('help.download_template', ['url' => route('admin.vendor.merchant.template')]) !!}</li>
        <li>{!! trans('help.subscription_plan_id_need_to_know') !!}</li>
        <li width="100%">{!! trans('help.required_fields_csv', ['fields' => implode(', ', config('system.import_required.merchant_upload', []))]) !!}</li>
        <li>{!! trans('help.first_row_as_header') !!}</li>
        <li>{!! trans('help.invalid_rows_will_ignored') !!}</li>
      </ul>

      <div class="row mt-4">
        <div class="col-md-9 nopadding-right">
          <input id="uploadFile" placeholder="{{ trans('app.placeholder.select_csv_file') }}" class="form-control" disabled="disabled" style="height: 28px;" />
        </div>
        <div class="col-md-3 nopadding-left">
          <div class="fileUpload btn btn-primary btn-block btn-flat">
            <span>{{ trans('app.form.select') }} CSV</span>
            <input type="file" name="merchants" id="uploadBtn" class="upload" />
          </div>
        </div>
      </div>
    </div>

    <div class="modal-footer">
      {!! Form::submit(trans('app.form.upload'), ['class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div> <!-- / .modal-content -->
</div> <!-- / .modal-dialog -->
