<link href={{ asset('css/fileinput.css') }} rel="stylesheet">
<script src={{ asset('js/fileinput.js') }}></script>
{{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-fileinput/4.4.5/js/locales/LANG.js"></script> --}}

<!-- page script -->
<script type="text/javascript">
  ;
  (function($, window, document) {
    $(document).ready(function() {
      var formData = new FormData();

      var footerTemplate = '<div class="file-thumbnail-footer">\n' +
        '    <small>{size}</small> {actions}\n' +
        '</div>';

      var actionsTemplate = '<div class="file-actions">\n' +
        '    <div class="file-footer-buttons">\n' +
        '       {download} {zoom} {other} {delete}' +
        '    </div>\n' +
        '    {drag}\n' +
        '    <div class="clearfix"></div>\n' +
        '</div>';

      var deleteTemplate = '<button type="button" class="kv-file-remove btn btn-kv btn-default btn-flat btn-xs" data-toggle="tooltip" data-placement="top" title="{{ trans('app.remove') }}" {dataUrl}{dataKey}><i class="fa fa-trash"></i></button>\n';

      var downloadTemplate = '<a class="kv-file-download btn btn-default btn-flat btn-xs" data-toggle="tooltip" data-placement="top" title="{{ trans('app.download') }}" href="{downloadUrl}" download="" target="_blank"><i class="fa fa-download"></i></a>\n';

      var zoomTemplate = '<button type="button" class="kv-file-zoom btn btn-default btn-flat btn-xs"  data-toggle="tooltip" data-placement="top" title="{{ trans('app.preview') }}"><i class="fa fa-search-plus"></i></button>';

      var dragTemplate = '<span class="file-drag-handle {dragClass}" data-toggle="tooltip" data-placement="top" title="{{ trans('app.move') }}"><i class="fa fa-arrows"></i></span>';

      var initialPreview = [<?= isset($preview) ? $preview['urls'] : '' ?>];

      var initialPreviewConfig = [<?= isset($preview) ? $preview['configs'] : '' ?>];

      $("#dropzone-input").fileinput({
        uploadUrl: "{{ route('image.upload') }}",
        uploadExtraData: function() {
          var extra = {};
          extra['model_id'] = formData.get('model_id');
          @if(isset($inventory))
            extra['model_id'] = "{{ $inventory->id }}";
          @endif
          extra['model_name'] = formData.get('model_name');
          extra['redirect_url'] = formData.get('redirect_url');

          return extra;
        },
        showUpload: false,
        enableResumableUpload: true,
        resumableUploadOptions: {
          // testUrl: "/site/test-file-chunks",
          chunkSize: {{ config('image.chunk_size', 1024) }},
        },
        dropZoneEnabled: true,
        browseOnZoneClick: true,
        dropZoneTitle: "{{ trans('app.drag_n_drop_here') }}",
        showClose: false,
        showRemove: false,
        showCaption: false,
        maxFilePreviewSize: 25600,
        minFileSize: {{ getAllowedMinImgSize() }},
        maxFileSize: {{ getAllowedMaxImgSize() }},
        minFileCount: {{ getMinNumberOfRequiredImgsForInventory() }},
        maxTotalFileCount: {{ getMaxNumberOfImgsForInventory() }},
        allowedFileExtensions: ['jpg', 'jpeg', 'gif', 'png', 'webp', 'svg'],
        msgFilesTooLess: "{!! trans('help.number_of_img_upload_required') !!}",
        msgTotalFilesTooMany: "{!! trans('help.number_of_img_upload_exceeded') !!}",
        msgInvalidFileExtension: "{!! trans('help.msg_invalid_file_extension') !!}",
        msgSizeTooLarge: "{!! trans('help.msg_invalid_file_too_large') !!}",
        dragSettings: {
          animation: 300,
          onUpdate: function(evt) {
            console.log(evt);
          },
        },
        initialPreview: initialPreview,
        overwriteInitial: false,
        initialPreviewAsData: true,
        initialPreviewFileType: 'image',
        initialPreviewDownloadUrl: "{{ url('download/{key}') }}",
        initialPreviewConfig: initialPreviewConfig,
        layoutTemplates: {
          footer: footerTemplate,
          actions: actionsTemplate,
          actionDelete: deleteTemplate,
          actionDownload: downloadTemplate,
          actionZoom: zoomTemplate,
          actionDrag: dragTemplate
        },
      }).on('filebeforedelete', function() {
        return new Promise(function(resolve, reject) {
          $.confirm({
            title: "{{ trans('app.confirmation') }}",
            content: "{{ trans('app.are_you_sure') }}",
            type: 'red',
            buttons: {
              'confirm': {
                text: '{{ trans('app.proceed') }}',
                keys: ['enter'],
                btnClass: 'btn-red',
                action: function() {
                  resolve();
                }
              },
              'cancel': {
                text: '{{ trans('app.cancel') }}',
                action: function() {
                  notie.alert(2, "{{ trans('messages.canceled') }}", 3);
                }
              },
            }
          });
        });
      }).on('filedeleted', function() {
        setTimeout(function() {
          notie.alert(1, "{{ trans('messages.file_deleted') }}", 3);
        }, 900);
      }).on('filesorted', function(event, params) {
        var sortUrl = "{{ route('image.sort') }}";
        var max = Math.max(params.oldIndex, params.newIndex);
        var min = Math.min(params.oldIndex, params.newIndex);
        var order = {};
        var stack = params.stack;
        for (k in stack) {
          if (k >= min && k <= max)
            order[stack[k].key] = k;
        };

        // Update the database using AJAX
        $.post(sortUrl, order, function(theResponse, status) {
          notie.alert(1, "{{ trans('responses.reordered') }}", 2);
        });
      }).on('fileuploaded', function(event, previewId, index, fileId) {
        console.log('File Uploaded', 'ID: ' + fileId + ', Thumb ID: ' + previewId);
      }).on('fileuploaderror', function(event, previewId, index, fileId) {
        console.log('File Upload Error', 'ID: ' + fileId + ', Thumb ID: ' + previewId);
      }).on('filebatchuploadcomplete', function(event, preview, config, tags, extraData) {
        console.log('File Batch Uploaded', preview, config, tags, extraData);
        var redirectUrl = extraData.redirect_url;
        if (typeof toPanelUrl === 'function') {
          redirectUrl = toPanelUrl(redirectUrl);
        }
        window.location.href = redirectUrl;
      });

      $('div.btn.btn-primary.btn-file').hide();

      $('#form-ajax-upload').on('submit', function(event) {
        event.preventDefault();
        $(this).find(":submit").prop("disabled", true);

        var action = $(this).attr('action');
        if (typeof toPanelUrl === 'function') {
          action = toPanelUrl(action);
        }

        var data = $("#form-ajax-upload").serializeArray();

        // if has download limit field then append file field to the form
        if (Object.keys(data).some(key => data[key].name === 'download_limit')) {
          var additionalFile = $("#digital_file")[0].files[0];
          if (typeof additionalFile === 'undefined') {
            var additionalFile = '';
          }
          formData.append("digital_file", additionalFile);
        }

        // Reset the key_features fields to avoid duplicate entry
        formData.delete('key_features[]');

        $.each(data, function(key, input) {
          formData.append(input.name, input.value);
        });

        // Append variant imgs
        $.each($(".variant-img"), function(i, file) {
          formData.append(file.name, $(".variant-img")[i].files[0]);
        });

        // Featured image upload inside the unified product editor.
        // Scope to the current product form to avoid grabbing another #uploadBtn
        // from other components/modals.
        var $featuredUpload = $("#form-ajax-upload").find("#uploadBtn");
        if ($featuredUpload.length && $featuredUpload.val()) {
          var file = $featuredUpload[0].files[0];
          if (file) {
            formData.append("images[feature]", file);
          }
        }

        // Product video (serializeArray skips file inputs)
        formData.delete('video');
        var videoInput = $("#form-ajax-upload").find('#product_video_input, input[name="video"]')[0];
        if (videoInput && videoInput.files && videoInput.files[0]) {
          formData.append('video', videoInput.files[0]);
        }

        if (typeof apply_busy_filter === 'function') {
          apply_busy_filter();
        }

        window.__keepCatalogBusy = false;

        $.ajax({
            url: action,
            type: "POST",
            datatype: "json",
            data: formData,
            processData: false, // tell jQuery not to process the data
            contentType: false, // tell jQuery not to set contentType
          })
          .done(function(result) {
            // Keep loading overlay until redirect / image batch upload finishes.
            window.__keepCatalogBusy = true;
            if (typeof apply_busy_filter === 'function') {
              apply_busy_filter();
            }

            formData.append('model_id', result.id);
            formData.append('model_name', result.model);
            formData.append('redirect_url', (typeof toPanelUrl === 'function') ? toPanelUrl(result.redirect) : result.redirect);

            var node = $('#dropzone-input');
            if (node.length && node.fileinput("getFilesCount") > 0) { // Upload only if there is files
              node.fileinput('upload').fileinput('disable');
            } else {
              window.location.href = (typeof toPanelUrl === 'function') ? toPanelUrl(result.redirect) : result.redirect;
            }
          })
          .fail(function(xhr) {
            window.__keepCatalogBusy = false;
            if (typeof remove_busy_filter === 'function') {
              remove_busy_filter();
            }
            $("#form-ajax-upload").find(":submit").removeAttr("disabled");
            var err = '';
            if (401 === xhr.status) {
              window.location = "{{ route('login') }}";
            } else if (422 === xhr.status) {
              notie.alert(3, "{{ trans('responses.form_validation_failed') }}", 3);
              var response = xhr.responseJSON;

              var $form = $("#form-ajax-upload");
              $form.find(".has-error").removeClass("has-error");
              $form.find(".is-invalid").removeClass("is-invalid");

              var firstInvalidEl = null;
              // DEBUG (temporary): log validation payload + matching.
              console.group('[Debug][Admin product editor] Validation 422 payload');
              console.log('responseJSON:', response);
              console.log('response.errors:', response && response.errors);
              console.groupEnd();

              function pickFieldElement(fieldKey) {
                // 1) Exact match for the error key (rare)
                var $el = $form.find('[name="' + fieldKey + '"]');
                if ($el.length) return $el.first();

                // 2) dot notation -> bracket notation: images.feature => images[feature]
                var bracketName = fieldKey.replace(/\.(\w+)/g, '[$1]');
                $el = $form.find('[name="' + bracketName + '"]');
                if ($el.length) return $el.first();

                // 3) fallback: match by first segment (category_list.0 => category_list[])
                var firstSegment = fieldKey.split('.')[0];
                $el = $form.find('[name^="' + firstSegment + '"]');
                if ($el.length) return $el.first();

                return $();
              }

              $.each(response.errors, function(fieldKey, messages) {
                var msgArr = Array.isArray(messages) ? messages : [messages];
                if (msgArr.length === 0) return;

                var $el = pickFieldElement(fieldKey);
                if ($el && $el.length) {
                  var $group = $el.closest(".form-group");
                  var labelText = $group.find("label").first().text().trim();
                  if (!labelText) {
                    // Some editor fields (like categories / featured image) don’t have a <label>.
                    // Their caption is in the panel title.
                    var panelTitle = $el.closest(".wc-panel").find(".wc-panel__title").first().text().trim();
                    labelText = panelTitle;
                  }

                  if (!labelText) {
                    // Slug caption in the unified editor is not a <label> tag.
                    var permalinkText = $el.closest(".wc-permalink").find(".text-muted").first().text().trim();
                    labelText = permalinkText;
                  }

                  // Remove trailing * (required marker) if present.
                  labelText = labelText ? labelText.replace(/\*\s*$/, '').trim() : '';
                  labelText = labelText ? labelText.replace(/:\s*$/, '').trim() : '';
                  var displayName = labelText || fieldKey;

                  console.log('[Debug][Admin product editor] fieldKey=', fieldKey, 'displayName=', displayName, 'pickedElName=', $el.attr('name'));

                  err += '<strong>' + displayName + ':</strong> ' + msgArr[0] + '<br/>';
                } else {
                  err += '<strong>' + fieldKey + ':</strong> ' + msgArr[0] + '<br/>';
                  return;
                }

                $el.addClass("is-invalid");

                var $group = $el.closest(".form-group");
                if ($group.length) {
                  $group.addClass("has-error");
                  // Put the first error message under the field (best-effort).
                  var $help = $group.find(".help-block.with-errors, .help-block, .help-inline").first();
                  if ($help.length) {
                    $help.html(msgArr[0]);
                  }
                }

                if (!firstInvalidEl) {
                  firstInvalidEl = $el;
                }
              });

              // If for some reason Laravel didn't return field errors in the expected shape,
              // show something useful so we can debug quickly.
              if (!err) {
                if (response && response.message) {
                  err = response.message;
                } else {
                  err = $('<div>').text(JSON.stringify(response || {})).html();
                }
              }

              if (firstInvalidEl && firstInvalidEl.length) {
                try {
                  firstInvalidEl[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                } catch (e) {
                  // ignore
                }
              }
            } else if (500 === xhr.status) {
              // Server-side exception — show the message returned from our try/catch handler.
              var resp500 = xhr.responseJSON;
              if (resp500 && resp500.message) {
                err = resp500.message;
                if (resp500.error) {
                  err += '<br><small class="text-muted">' + $('<div>').text(resp500.error).html() + '</small>';
                }
              } else {
                err = "{{ trans('messages.product_create_failed') }}";
              }
              notie.alert(3, err, 5);
            } else {
              err += "{{ trans('responses.error') }}";
            }

            if (!err) { return; }

            var msg = '<div class="alert alert-danger alert-dismissible">' +
              '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' +
              '<h4><i class="icon fa fa-warning"></i>{{ trans('app.error') }}</h4>' +
              '<p id="global-alert-msg">' + err + '</p>' +
              '</div>';
            // On merchant panel layout there is no `section.content`.
            var $container = $("section.content");
            if (!($container && $container.length)) {
              $container = $(".mp-content--admin, .mp-content").first();
            }
            $container.prepend(msg);
          });

        return false;
      });
    });
  }(window.jQuery, window, document));
</script>
