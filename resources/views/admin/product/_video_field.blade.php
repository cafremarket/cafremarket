@php
  $videoProduct = $product ?? (isset($inventory) ? $inventory->product : null);
  $existingVideoPath = filled(optional($videoProduct)->video_path) ? $videoProduct->video_path : null;
  $hasExistingVideo = filled($existingVideoPath);
  $existingVideoUrl = $hasExistingVideo ? get_product_video_url($existingVideoPath) : null;
  if ($hasExistingVideo && ! $existingVideoUrl) {
      $existingVideoUrl = url('image/'.$existingVideoPath);
  }
  $existingVideoType = null;
  if ($existingVideoPath) {
      $ext = strtolower(pathinfo($existingVideoPath, PATHINFO_EXTENSION));
      $existingVideoType = match ($ext) {
          'webm' => 'video/webm',
          'mov' => 'video/quicktime',
          'm4v' => 'video/x-m4v',
          default => 'video/mp4',
      };
  }
@endphp

<div class="product-video-field" id="product_video_field">
  <div class="form-group mb-0">
    <label for="product_video_input">
      {{ trans('app.product_video') }}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.product_video') }}"></i>
    </label>

    <div class="product-video-field__preview{{ $existingVideoUrl ? ' is-visible' : '' }}"
         id="product_video_preview_wrap"
         @unless ($existingVideoUrl) hidden @endunless>
      <video id="product_video_preview"
             controls
             preload="metadata"
             playsinline
             @if ($existingVideoUrl) src="{{ $existingVideoUrl }}" @endif
             @if ($existingVideoType) type="{{ $existingVideoType }}" @endif>
      </video>

      @if ($hasExistingVideo)
        <label class="product-video-field__delete" id="product_video_delete_label">
          {!! Form::checkbox('delete_video', 1, null, ['class' => 'icheck', 'id' => 'product_video_delete']) !!}
          {{ trans('app.form.delete_video') }}
        </label>
        <p class="help-block small mb-0 text-muted" id="product_video_current_label">
          {{ trans('help.product_video_current') }}
        </p>
      @endif
    </div>

    <div class="product-video-field__empty" id="product_video_empty"
         @if ($existingVideoUrl) hidden @endif>
      <i class="fa fa-film" aria-hidden="true"></i>
      <span>{{ trans('help.product_video_preview_hint') }}</span>
    </div>

    <input type="file"
           name="video"
           id="product_video_input"
           class="form-control"
           accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov,.m4v">

    <p class="help-block small mb-0">
      {{ trans('help.product_video_limits') }}
    </p>
    <p class="help-block small text-danger mb-0" id="product_video_error" hidden></p>
  </div>
</div>

<script>
(function () {
  function bootProductVideoPreview() {
    var input = document.getElementById('product_video_input');
    var errorEl = document.getElementById('product_video_error');
    var previewWrap = document.getElementById('product_video_preview_wrap');
    var preview = document.getElementById('product_video_preview');
    var emptyEl = document.getElementById('product_video_empty');
    var deleteLabel = document.getElementById('product_video_delete_label');
    var currentLabel = document.getElementById('product_video_current_label');
    if (!input || !preview || !previewWrap || input.dataset.previewBound === '1') return;
    input.dataset.previewBound = '1';

    var maxBytes = 10 * 1024 * 1024;
    var maxSeconds = 30;
    var msgSize = @json(trans('validation.product_video_max'));
    var msgDuration = @json(trans('validation.product_video_duration', ['seconds' => 30]));
    var existingSrc = @json($existingVideoUrl);
    var existingType = @json($existingVideoType);
    var objectUrl = null;

    function showError(msg) {
      if (!errorEl) return;
      errorEl.textContent = msg || '';
      errorEl.hidden = !msg;
    }

    function revokeObjectUrl() {
      if (!objectUrl) return;
      URL.revokeObjectURL(objectUrl);
      objectUrl = null;
    }

    function setVisible(el, visible) {
      if (!el) return;
      el.hidden = !visible;
      el.style.display = visible ? '' : 'none';
    }

    function showPreview(src, options) {
      options = options || {};
      var isExisting = !!options.isExisting;
      var mime = options.mime || null;

      // Only revoke a previous blob when replacing it with something else.
      if (objectUrl && src !== objectUrl) {
        revokeObjectUrl();
      }

      try { preview.pause(); } catch (e) {}

      if (!src) {
        revokeObjectUrl();
        preview.removeAttribute('src');
        preview.removeAttribute('type');
        preview.load();
        setVisible(previewWrap, false);
        previewWrap.classList.remove('is-visible');
        setVisible(emptyEl, true);
        return;
      }

      if (mime) {
        preview.setAttribute('type', mime);
      } else if (isExisting && existingType) {
        preview.setAttribute('type', existingType);
      } else {
        preview.removeAttribute('type');
      }

      preview.src = src;
      preview.load();
      setVisible(previewWrap, true);
      previewWrap.classList.add('is-visible');
      setVisible(emptyEl, false);
      setVisible(deleteLabel, isExisting);
      setVisible(currentLabel, isExisting);
    }

    function restoreExisting() {
      if (existingSrc) {
        showPreview(existingSrc, { isExisting: true, mime: existingType });
      } else {
        showPreview(null);
      }
    }

    input.addEventListener('change', function () {
      showError('');
      var file = input.files && input.files[0];
      if (!file) {
        restoreExisting();
        return;
      }

      if (file.size > maxBytes) {
        input.value = '';
        showError(msgSize);
        restoreExisting();
        return;
      }

      revokeObjectUrl();
      objectUrl = URL.createObjectURL(file);
      showPreview(objectUrl, {
        isExisting: false,
        mime: file.type || 'video/mp4'
      });

      var probe = document.createElement('video');
      probe.preload = 'metadata';
      probe.onloadedmetadata = function () {
        var duration = probe.duration || 0;
        if (duration > maxSeconds + 0.5) {
          input.value = '';
          showError(msgDuration);
          restoreExisting();
        }
      };
      probe.src = objectUrl;
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootProductVideoPreview);
  } else {
    bootProductVideoPreview();
  }
})();
</script>
