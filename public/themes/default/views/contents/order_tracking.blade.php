<section class="sf-detail-page">
  <div class="container my-3">
    <div class="sf-track-box">
      @if ($order->carrier_id && $order->getTrackingUrl())
        <iframe src="{{ $order->getTrackingUrl() }}" title="@lang('theme.button.track_order')"></iframe>
      @else
        <div class="input-group mb-3">
          <input id="YQNum" value="{{ $order->tracking_id }}" placeholder="@lang('theme.help.give_tracking_number_here')" class="form-control" type="text" maxlength="50" required="required" />
          <span class="input-group-btn">
            <button class="btn sf-btn-primary" type="button" onclick="doTrack()">@lang('theme.button.track_order')</button>
          </span>
        </div>
        <div id="YQContainer"></div>
      @endif
    </div>
  </div>
</section>

<script type="text/javascript" src="//www.17track.net/externalcall.js"></script>
<script type="text/javascript">
  function doTrack() {
    var num = document.getElementById("YQNum").value;
    if (num === "") {
      alert("Enter your number.");
      return;
    }
    YQV5.trackSingle({
      YQ_ContainerId: "YQContainer",
      YQ_Height: 560,
      YQ_Fc: "0",
      YQ_Lang: "en",
      YQ_Num: num
    });
  }
</script>
