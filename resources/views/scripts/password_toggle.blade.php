<style>
  .password-input-wrap {
    position: relative;
  }
  .password-input-wrap > .form-control,
  .password-input-wrap > input[type="password"],
  .password-input-wrap > input[type="text"] {
    padding-right: 44px !important;
  }
  .password-input-wrap.has-feedback > .form-control,
  .password-input-wrap.has-feedback > input[type="password"],
  .password-input-wrap.has-feedback > input[type="text"] {
    padding-right: 76px !important;
  }
  .password-input-wrap .form-control-feedback {
    position: absolute;
    top: 0;
    right: 40px;
    z-index: 3;
    display: block;
    width: 34px;
    height: 100%;
    line-height: inherit;
    text-align: center;
    pointer-events: none;
  }
  .password-input-wrap .form-control-feedback:before {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
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

  /* AdminLTE login: keep lock + eye from stacking on the same spot */
  .login-box-body .password-input-wrap.has-feedback > .form-control,
  .register-box-body .password-input-wrap.has-feedback > .form-control {
    padding-right: 80px !important;
  }
  .login-box-body .password-input-wrap .form-control-feedback,
  .register-box-body .password-input-wrap .form-control-feedback {
    width: 34px;
    height: 100%;
    line-height: normal;
    right: 42px;
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

      var parent = input.parentNode;
      var feedback = null;
      var sibling = input.nextElementSibling;
      if (sibling && sibling.classList && sibling.classList.contains('form-control-feedback')) {
        feedback = sibling;
      } else if (parent) {
        feedback = parent.querySelector(':scope > .form-control-feedback');
      }

      if (feedback || input.closest('.form-group.has-feedback')) {
        wrap.classList.add('has-feedback');
      }

      parent.insertBefore(wrap, input);
      wrap.appendChild(input);

      if (feedback) {
        wrap.appendChild(feedback);
      }

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
