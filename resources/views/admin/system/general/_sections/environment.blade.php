
          <div class="spacer30"></div>
          @if (Auth::guard('web')->user()->isSuperAdmin())
            <div class="row">
              <div class="col-sm-4 text-center">
                <a href="javascript:void(0)" data-link="{{ route('admin.setting.system.modifyEnvFile') }}" class="ajax-modal-btn btn btn-danger btn-lg ">
                  {{ trans('app.modify_environment_file') }}
                </a>

                <div class="spacer10"></div>

                <p class="text-danger">
                  <i class="fa fa-exclamation-triangle"></i> {!! trans('messages.modify_environment_file') !!}
                </p>
              </div><!-- /.col-sm-4 -->

              <div class="col-sm-4 text-center">
                <a href="{{ route('admin.incevio.clear') }}" class="btn btn-default btn-lg confirm">
                  {{ trans('app.clear_cache') }}
                </a>

                <div class="spacer10"></div>

                <p class="text-danger">
                  <i class="fa fa-info-circle"></i> {!! trans('help.help_clear_cache') !!}
                </p>
              </div><!-- /.col-sm-4 -->

              <div class="col-sm-4 text-center">
                @if (config('app.demo') !== true)
                  <a href="{{ route('admin.setting.system.backup') }}" class="btn btn-default btn-lg confirm">
                    {{ trans('app.take_a_backup') }}
                  </a>
                @else
                  <button class="btn btn-default btn-lg disabled">{{ trans('app.take_a_backup') }}</button>

                  <p class="text-warning">{{ trans('messages.demo_restriction') }}</p>
                @endif

                <div class="spacer10"></div>

                <p class="text-info">
                  <i class="fa fa-info-circle"></i> {!! trans('messages.take_a_backup') !!}
                </p>
              </div><!-- /.col-sm-4 -->
            </div><!-- /.row -->

            <div class="spacer30"></div>

            <div class="row">
              @unless (config('app.demo') == true)
                <hr class="style3" />
                <div class="col-sm-4 text-center">
                  <p class="text-danger">
                    <i class="fa fa-exclamation-triangle"></i> {!! trans('messages.import_demo_contents') !!}
                  </p>

                  <a href="javascript:void(0)" data-link="{{ route('admin.setting.system.importDemoContents') }}" class="ajax-modal-btn btn btn-danger btn-lg ">
                    {{ trans('app.import_demo_contents') }}
                  </a>
                </div>

                <div class="col-sm-4 text-center">
                  <p class="text-danger">
                    <i class="fa fa-exclamation-triangle"></i> {!! trans('messages.clear_demo_contents') !!}
                  </p>
                  <a href="javascript:void(0)" data-link="{{ route('admin.setting.system.clearDemoContents') }}" class="ajax-modal-btn btn btn-default btn-lg ">
                    {{ trans('app.clear_demo_contents') }}
                  </a>
                </div><!-- /.col-sm-4 -->
              @endunless
            </div><!-- /.row -->
          @endif
          <div class="spacer50"></div>
