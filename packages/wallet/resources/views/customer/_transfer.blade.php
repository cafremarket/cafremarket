<div class="col-md-6 col-md-offset-2 my-5">
  <div class="panel panel-default">
    <div class="panel-heading">{{ trans('packages.wallet.transfer_balance') }}</div>
    <div class="panel-body">
      {!! Form::open(['route' => 'customer.account.wallet.transfer', 'id' => 'form', 'data-toggle' => 'validator']) !!}

      @include('wallet::_transfer_form')

      <button id="pay-now-btn" class="btn btn-primary btn-lg btn-block" type="submit">
        <small><i class="fa fa-shield"></i>
          <span id="pay-now-btn-txt">@lang('packages.wallet.transfer')</span>
        </small>
      </button>
      {!! Form::close() !!}
    </div>
  </div>

  <script type="text/javascript">
    var radios = document.querySelectorAll('input[name="recipient_type"]');

    var parent = document.getElementById('transfer_input_form');
    var vendor_element = document.getElementById('transfer_to_vendor');
    var customer_element = document.getElementById('transfer_to_customer');

    vendor_element.classList.add('hidden'); // Hide the vendor element by default

    // Removed only after being loaded to include bootstrap styling
    window.onload = function() {
      var parent = document.getElementById('transfer_input_form');
      var vendor_element = document.getElementById('transfer_to_vendor');
      parent.removeChild(vendor_element);
    }

    radios.forEach(function(radio) {
      radio.addEventListener('change', function() {
        var parent = document.getElementById('transfer_input_form'); // The parent of the element to be removed/added
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
