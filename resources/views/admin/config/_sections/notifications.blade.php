
          <div class="row mb-5">
            <div class="col-sm-6 col-sm-offset-2">
              <fieldset>
                <legend>{{ trans('app.inventory') }}</legend>
                <div class="row">
                  <div class="col-sm-8 text-right">
                    <div class="form-group">
                      {!! Form::label('notify_alert_quantity', trans('app.notify_alert_quantity') . ':', ['class' => 'with-help control-label']) !!}
                      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.notify_alert_quantity') }}"></i>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    @if ($can_update)
                      <div class="handle horizontal">
                        <a href="javascript:void(0)" data-link="{{ route('admin.setting.config.notification.toggle', 'notify_alert_quantity') }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $config->notify_alert_quantity == 1 ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $config->notify_alert_quantity == 1 ? 'true' : 'false' }}" autocomplete="off">
                          <div class="btn-handle"></div>
                        </a>
                      </div>
                    @else
                      <span>{{ $config->notify_alert_quantity == 1 ? trans('app.on') : trans('app.off') }}</span>
                    @endif
                  </div>
                </div> <!-- /.row -->

                <div class="row">
                  <div class="col-sm-8 text-right">
                    <div class="form-group">
                      {!! Form::label('notify_inventory_out', trans('app.notify_inventory_out') . ':', ['class' => 'with-help control-label']) !!}
                      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.notify_inventory_out') }}"></i>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    @if ($can_update)
                      <div class="handle horizontal">
                        <a href="javascript:void(0)" data-link="{{ route('admin.setting.config.notification.toggle', 'notify_inventory_out') }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $config->notify_inventory_out == 1 ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $config->notify_inventory_out == 1 ? 'true' : 'false' }}" autocomplete="off">
                          <div class="btn-handle"></div>
                        </a>
                      </div>
                    @else
                      <span>{{ $config->notify_inventory_out == 1 ? trans('app.on') : trans('app.off') }}</span>
                    @endif
                  </div>
                </div> <!-- /.row -->
              </fieldset>

              <fieldset>
                <legend>{{ trans('app.order') }}</legend>
                <div class="row">
                  <div class="col-sm-8 text-right">
                    <div class="form-group">
                      {!! Form::label('notify_new_order', trans('app.notify_new_order') . ':', ['class' => 'with-help control-label']) !!}
                      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.notify_new_order') }}"></i>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    @if ($can_update)
                      <div class="handle horizontal">
                        <a href="javascript:void(0)" data-link="{{ route('admin.setting.config.notification.toggle', 'notify_new_order') }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $config->notify_new_order == 1 ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $config->notify_new_order == 1 ? 'true' : 'false' }}" autocomplete="off">
                          <div class="btn-handle"></div>
                        </a>
                      </div>
                    @else
                      <span>{{ $config->notify_new_order == 1 ? trans('app.on') : trans('app.off') }}</span>
                    @endif
                  </div>
                </div> <!-- /.row -->

                <div class="row">
                  <div class="col-sm-8 text-right">
                    <div class="form-group">
                      {!! Form::label('notify_abandoned_checkout', trans('app.notify_abandoned_checkout') . ':', ['class' => 'with-help control-label']) !!}
                      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.notify_abandoned_checkout') }}"></i>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    @if ($can_update)
                      <div class="handle horizontal">
                        <a href="javascript:void(0)" data-link="{{ route('admin.setting.config.notification.toggle', 'notify_abandoned_checkout') }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $config->notify_abandoned_checkout == 1 ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $config->notify_abandoned_checkout == 1 ? 'true' : 'false' }}" autocomplete="off">
                          <div class="btn-handle"></div>
                        </a>
                      </div>
                    @else
                      <span>{{ $config->notify_abandoned_checkout == 1 ? trans('app.on') : trans('app.off') }}</span>
                    @endif
                  </div>
                </div> <!-- /.row -->

                <div class="row">
                  <div class="col-sm-8 text-right">
                    <div class="form-group">
                      {!! Form::label('notify_new_disput', trans('app.notify_new_dispute') . ':', ['class' => 'with-help control-label']) !!}
                      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.notify_new_dispute') }}"></i>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    @if ($can_update)
                      <div class="handle horizontal">
                        <a href="javascript:void(0)" data-link="{{ route('admin.setting.config.notification.toggle', 'notify_new_disput') }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $config->notify_new_disput == 1 ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $config->notify_new_disput == 1 ? 'true' : 'false' }}" autocomplete="off">
                          <div class="btn-handle"></div>
                        </a>
                      </div>
                    @else
                      <span>{{ $config->notify_new_disput == 1 ? trans('app.on') : trans('app.off') }}</span>
                    @endif
                  </div>
                </div> <!-- /.row -->
              </fieldset>

              <fieldset>
                <legend>{{ trans('app.support') }}</legend>
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
                        <a href="javascript:void(0)" data-link="{{ route('admin.setting.config.notification.toggle', 'notify_new_message') }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $config->notify_new_message == 1 ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $config->notify_new_message == 1 ? 'true' : 'false' }}" autocomplete="off">
                          <div class="btn-handle"></div>
                        </a>
                      </div>
                    @else
                      <span>{{ $config->notify_new_message == 1 ? trans('app.on') : trans('app.off') }}</span>
                    @endif
                  </div>
                </div> <!-- /.row -->

                <div class="row">
                  <div class="col-sm-8 text-right">
                    <div class="form-group">
                      {!! Form::label('notify_new_chat', trans('app.notify_new_chat') . ':', ['class' => 'with-help control-label']) !!}
                      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="left" title="{{ trans('help.notify_new_chat') }}"></i>
                    </div>
                  </div>
                  <div class="col-sm-4">
                    @if ($can_update)
                      <div class="handle horizontal">
                        <a href="javascript:void(0)" data-link="{{ route('admin.setting.config.notification.toggle', 'notify_new_chat') }}" type="button" class="btn btn-md btn-secondary btn-toggle {{ $config->notify_new_chat == 1 ? 'active' : '' }}" data-toggle="button" aria-pressed="{{ $config->notify_new_chat == 1 ? 'true' : 'false' }}" autocomplete="off">
                          <div class="btn-handle"></div>
                        </a>
                      </div>
                    @else
                      <span>{{ $config->notify_new_chat == 1 ? trans('app.on') : trans('app.off') }}</span>
                    @endif
                  </div>
                </div> <!-- /.row -->
              </fieldset>
            </div> <!-- /.col-sm-* -->
          </div> <!-- /.row -->
