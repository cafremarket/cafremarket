<style>
  .theme-select {
    position: absolute;
    top: 350px;
    left: 0;
    background-color: #cc5200;
    color: #f0f0f0;
    border-radius: 0 6px 6px 0;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.25);
    z-index: 9999999;
  }
</style>

<select class="theme-select" id="zcart-js-theme-select">
  <option value="light">{{ trans('app.theme_light') }}</option>
  <option value="dark">{{ trans('app.theme_dark') }}</option>
  <option value="blue">{{ trans('app.theme_blue_rose') }}</option>
</select>
