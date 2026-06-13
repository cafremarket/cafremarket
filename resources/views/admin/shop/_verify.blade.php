<div class="modal-dialog modal-md">
  <div class="modal-content">
    {!! Form::open(['method' => 'POST', 'route' => ['admin.vendor.shop.verify.approve', $shop->id], 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('app.review_verification_request') }} - {{ $shop->name }}
    </div>
    <div class="modal-body">
      @if (optional($shop->config)->pending_verification)
        <div class="alert alert-info">
          {{ trans('messages.verification_request_pending_review') }}
        </div>
      @endif

      @if (optional($shop->config)->attachments && $shop->config->attachments->count())
        <div class="form-group">
          <label>{{ trans('app.uploaded_documents') }}</label>
          <ul class="list-group">
            @foreach ($shop->config->attachments as $attachment)
              <li class="list-group-item">
                <a href="{{ route('attachment.download', $attachment) }}">
                  <i class="fa fa-cloud-download"></i> {{ $attachment->name }}
                </a>
                <small class="text-muted">({{ get_formated_file_size($attachment->size) }})</small>
              </li>
            @endforeach
          </ul>
        </div>
      @else
        <p class="text-muted">{{ trans('messages.no_verification_documents_uploaded') }}</p>
      @endif

      <div class="form-group">
        <div class="input-group">
          {{ Form::hidden('id_verified', 0) }}
          {!! Form::checkbox('id_verified', 1, $shop->id_verified, ['id' => 'id_verified', 'class' => 'icheckbox_line']) !!}
          {!! Form::label('id_verified', trans('app.id_verified')) !!}
        </div>
      </div>

      <div class="form-group">
        <div class="input-group">
          {{ Form::hidden('address_verified', 0) }}
          {!! Form::checkbox('address_verified', 1, $shop->address_verified, ['id' => 'address_verified', 'class' => 'icheckbox_line']) !!}
          {!! Form::label('address_verified', trans('app.address_verified')) !!}
        </div>
      </div>

      <div class="form-group">
        <div class="input-group">
          {{ Form::hidden('phone_verified', 0) }}
          {!! Form::checkbox('phone_verified', 1, $shop->phone_verified, ['id' => 'phone_verified', 'class' => 'icheckbox_line']) !!}
          {!! Form::label('phone_verified', trans('app.phone_verified')) !!}
        </div>
      </div>

      <div class="form-group">
        <div class="input-group">
          {{ Form::hidden('active', 0) }}
          {!! Form::checkbox('active', 1, $shop->active, ['id' => 'active', 'class' => 'icheckbox_line']) !!}
          {!! Form::label('active', trans('app.active')) !!}
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <a href="javascript:void(0)" data-link="{{ route('admin.vendor.shop.verify.reject.form', $shop) }}" class="ajax-modal-btn btn btn-danger btn-flat pull-left">{{ trans('app.reject_verification') }}</a>
      {!! Form::submit(trans('app.approve_verification'), ['class' => 'btn btn-success btn-flat']) !!}
    </div>
    {!! Form::close() !!}
  </div>
</div>
