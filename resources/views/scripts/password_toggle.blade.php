<style>
  .password-input-wrap {
    position: relative;
  }
  .password-input-wrap > .form-control,
  .password-input-wrap > input[type="password"],
  .password-input-wrap > input[type="text"] {
    padding-right: 44px !important;
  }
  .password-input-wrap.has-feedback > .form-control {
    padding-right: 68px !important;
  }
  .password-input-wrap.has-feedback .form-control-feedback {
    right: 36px;
    pointer-events: none;
  }
  .password-toggle-btn {
    position: absolute;
    right: 4px;
    top: 50%;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: #6b7280;
    z-index: 4;
    width: 36px;
    height: 36px;
    padding: 0;
    line-height: 36px;
    text-align: center;
    cursor: pointer;
    border-radius: 6px;
  }
  .password-toggle-btn:hover,
  .password-toggle-btn:focus {
    color: #2563eb;
    background: rgba(37, 99, 235, 0.08);
    outline: none;
  }
  .password-toggle-btn .fa {
    font-size: 16px;
    pointer-events: none;
  }
</style>

<script>
(function() {
  var showLabel = @json(trans('app.show_password'));
  var hideLabel = @json(trans('app.hide_password'));

  function initPasswordToggles(root) {
    var scope = root && root.querySelectorAll ? root : document;

    scope.querySelectorAll('input[type="password"]').forEach(function(input) {
      if (input.closest('.password-input-wrap') || input.dataset.passwordToggleInit === '1') {
        return;
      }

      input.dataset.passwordToggleInit = '1';

      var wrap = document.createElement('div');
      wrap.className = 'password-input-wrap';

      if (input.closest('.form-group.has-feedback')) {
        wrap.classList.add('has-feedback');
      }

      var parent = input.parentNode;
      parent.insertBefore(wrap, input);
      wrap.appendChild(input);

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'password-toggle-btn';
      btn.setAttribute('aria-label', showLabel);
      btn.innerHTML = '<i class="fa fa-eye" aria-hidden="true"></i>';

      btn.addEventListener('click', function() {
        var revealing = input.type === 'password';
        input.type = revealing ? 'text' : 'password';
        btn.innerHTML = revealing
          ? '<i class="fa fa-eye-slash" aria-hidden="true"></i>'
          : '<i class="fa fa-eye" aria-hidden="true"></i>';
        btn.setAttribute('aria-label', revealing ? hideLabel : showLabel);
      });

      wrap.appendChild(btn);
    });
  }

  window.initPasswordToggles = initPasswordToggles;

  document.addEventListener('DOMContentLoaded', function() {
    initPasswordToggles(document);
  });

  if (typeof jQuery !== 'undefined') {
    jQuery(document).on('shown.bs.modal', function(e) {
      initPasswordToggles(e.target);
    });
  }
})();
</script>
