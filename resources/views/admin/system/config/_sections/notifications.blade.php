
          <div class="row">
            <div class="col-sm-6">
              <fieldset>
                <legend>{{ trans('app.notifications') }}</legend>
                <div class="row">
                  <div class="col-sm-8 text-right">
                    <div class="form-group">
                      {!! Form::label('notify_when_vendor_registered', trans('app.notify_when_vendor_registered') . ':', ['class' => 'with-help control-label']) !!}
                      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.notify_when_vendor_registered') }}"></i>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    @if ($can_update)
                      <div class="handle horizontal">
                        <a href="javascript:void(0)" data-link="{{ route('admin.setting.system.config.toggle', 'notify_when_vendor_registered') }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $system->notify_when_vendor_registered ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $system->notify_when_vendor_registered ? 'true' : 'false' }}" autocomplete="off">
                          <div class="btn-handle"></div>
                        </a>
                      </div>
                    @else
                      <span>{{ $system->notify_when_vendor_registered ? trans('app.on') : trans('app.off') }}</span>
                    @endif
                  </div>
                </div> <!-- /.row -->

                <div class="row">
                  <div class="col-sm-8 text-right">
                    <div class="form-group">
                      {!! Form::label('notify_new_message', trans('app.notify_new_message') . ':', ['class' => 'with-help control-label']) !!}
                      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.notify_new_message') }}"></i>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    @if ($can_update)
                      <div class="handle horizontal">
                        <a href="javascript:void(0)" data-link="{{ route('admin.setting.system.config.toggle', 'notify_new_message') }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $system->notify_new_message ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $system->notify_new_message ? 'true' : 'false' }}" autocomplete="off">
                          <div class="btn-handle"></div>
                        </a>
                      </div>
                    @else
                      <span>{{ $system->notify_new_message ? trans('app.on') : trans('app.off') }}</span>
                    @endif
                  </div>
                </div> <!-- /.row -->

                <div class="row">
                  <div class="col-sm-8 text-right">
                    <div class="form-group">
                      {!! Form::label('notify_new_ticket', trans('app.notify_new_ticket') . ':', ['class' => 'with-help control-label']) !!}
                      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.notify_new_ticket') }}"></i>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    @if ($can_update)
                      <div class="handle horizontal">
                        <a href="javascript:void(0)" data-link="{{ route('admin.setting.system.config.toggle', 'notify_new_ticket') }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $system->notify_new_ticket ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $system->notify_new_ticket ? 'true' : 'false' }}" autocomplete="off">
                          <div class="btn-handle"></div>
                        </a>
                      </div>
                    @else
                      <span>{{ $system->notify_new_ticket ? trans('app.on') : trans('app.off') }}</span>
                    @endif
                  </div>
                </div> <!-- /.row -->

                <div class="row">
                  <div class="col-sm-8 text-right">
                    <div class="form-group">
                      {!! Form::label('notify_when_dispute_appealed', trans('app.notify_when_dispute_appealed') . ':', ['class' => 'with-help control-label']) !!}
                      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.notify_when_dispute_appealed') }}"></i>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    @if ($can_update)
                      <div class="handle horizontal">
                        <a href="javascript:void(0)" data-link="{{ route('admin.setting.system.config.toggle', 'notify_when_dispute_appealed') }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $system->notify_when_dispute_appealed ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $system->notify_when_dispute_appealed ? 'true' : 'false' }}" autocomplete="off">
                          <div class="btn-handle"></div>
                        </a>
                      </div>
                    @else
                      <span>{{ $system->notify_when_dispute_appealed ? trans('app.on') : trans('app.off') }}</span>
                    @endif
                  </div>
                </div> <!-- /.row -->
              </fieldset>
            </div> <!-- /.col-sm-6 -->

            <div class="col-sm-6">

            </div> <!-- /.col-sm-6 -->
          </div> <!-- /.row -->
