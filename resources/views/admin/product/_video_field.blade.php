@php
  $hasVideo = isset($product) && $product->hasVideo();
  $videoUrl = $hasVideo ? get_product_video_url($product->video_path) : null;
@endphp

<div class="product-video-field">
  <div class="form-group mb-0">
    <label for="product_video_input">
      {{ trans('app.product_video') }}
      <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="top" title="{{ trans('help.product_video') }}"></i>
    </label>

    @if ($hasVideo && $videoUrl)
      <div class="product-video-field__preview">
        <video src="{{ $videoUrl }}" controls preload="metadata" playsinline></video>
        <label class="product-video-field__delete">
          {!! Form::checkbox('delete_video', 1, null, ['class' => 'icheck']) !!}
          {{ trans('app.form.delete_video') }}
        </label>
      </div>
    @endif

    <input type="file"
           name="video"
           id="product_video_input"
           class="form-control"
           accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov,.m4v">

    <p class="help-block small mb-0">
      {{ trans('help.product_video_limits') }}
    </p>
    <p class="help-block small text-danger mb-0" id="product_video_error" style="display:none;"></p>
  </div>
</div>

<script>
(function () {
  var input = document.getElementById('product_video_input');
  var errorEl = document.getElementById('product_video_error');
  if (!input) return;

  var maxBytes = 10 * 1024 * 1024;
  var maxSeconds = 30;
  var msgSize = @json(trans('validation.product_video_max'));
  var msgDuration = @json(trans('validation.product_video_duration', ['seconds' => 30]));

  function showError(msg) {
    if (!errorEl) return;
    errorEl.textContent = msg || '';
    errorEl.style.display = msg ? '' : 'none';
  }

  input.addEventListener('change', function () {
    showError('');
    var file = input.files && input.files[0];
    if (!file) return;

    if (file.size > maxBytes) {
      input.value = '';
      showError(msgSize);
      return;
    }

    var url = URL.createObjectURL(file);
    var video = document.createElement('video');
    video.preload = 'metadata';
    video.onloadedmetadata = function () {
      URL.revokeObjectURL(url);
      var duration = video.duration || 0;
      if (duration > maxSeconds + 0.5) {
        input.value = '';
        showError(msgDuration);
      }
    };
    video.onerror = function () {
      URL.revokeObjectURL(url);
    };
    video.src = url;
  });
})();
</script>
