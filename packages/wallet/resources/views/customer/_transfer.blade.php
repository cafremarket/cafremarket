<div class="sf-wallet-page">
  <div class="sf-dashboard-welcome">
    <div class="sf-dashboard-welcome__top">
      <div class="sf-dashboard-welcome__greeting">
        <p class="sf-dashboard-welcome__eyebrow">@lang('packages.wallet.my_wallet')</p>
        <h2>@lang('packages.wallet.transfer')</h2>
        <p>@lang('packages.wallet.transfer_balance')</p>
      </div>

      <div class="sf-wallet-actions">
        <a href="{{ route('customer.account.wallet') }}" class="btn btn-default btn-sm">
          <i class="fas fa-arrow-left" aria-hidden="true"></i> @lang('packages.wallet.my_wallet')
        </a>
      </div>
    </div>
  </div>

  <div class="sf-form-panel" style="max-width: 640px; margin: 0 auto;">
    {!! Form::open(['route' => 'customer.account.wallet.transfer', 'id' => 'form', 'data-toggle' => 'validator', 'class' => 'sf-form']) !!}

    @include('wallet::_transfer_form')

    <button id="pay-now-btn" class="btn sf-btn-primary btn-lg btn-block" type="submit">
      <i class="fas fa-shield-alt" aria-hidden="true"></i>
      <span id="pay-now-btn-txt">@lang('packages.wallet.transfer')</span>
    </button>
    {!! Form::close() !!}
  </div>

  <script type="text/javascript">
    var radios = document.querySelectorAll('input[name="recipient_type"]');

    var parent = document.getElementById('transfer_input_form');
    var vendor_element = document.getElementById('transfer_to_vendor');
    var customer_element = document.getElementById('transfer_to_customer');

    vendor_element.classList.add('hidden');

    window.onload = function() {
      var parent = document.getElementById('transfer_input_form');
      var vendor_element = document.getElementById('transfer_to_vendor');
      parent.removeChild(vendor_element);
    }

    radios.forEach(function(radio) {
      radio.addEventListener('change', function() {
        var parent = document.getElementById('transfer_input_form');
        var userType = document.querySelector('input[name="recipient_type"]:checked').value;
        if (userType == 'customer') {
          parent.removeChild(vendor_element);
          parent.insertBefore(customer_element, parent.firstChild);
        } else {
          vendor_element.classList.remove('hidden');
          parent.removeChild(customer_element);
          parent.insertBefore(vendor_element, parent.firstChild);
        }
      });
    });
  </script>
</div>
