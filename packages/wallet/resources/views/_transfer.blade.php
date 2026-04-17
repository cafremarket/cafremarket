<div class="modal-dialog modal-sm">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      {{ trans('packages.wallet.transfer_balance') }}
    </div>
    {!! Form::open(['route' => 'merchant.wallet.transfer', 'id' => 'form', 'data-toggle' => 'validator']) !!}
    <div class="modal-body">

      @include('wallet::_transfer_form')

    </div>
    <div class="modal-footer">
      {!! Form::submit(trans('packages.wallet.transfer'), ['class' => 'btn btn-flat btn-new']) !!}
    </div>
    {!! Form::close() !!}
  </div> <!-- / .modal-content -->

  <script type="text/javascript">
    const radios = document.querySelectorAll('input[name="recipient_type"]');
    const parent = document.getElementById('transfer_input_form');
    const vendor_element = document.getElementById('transfer_to_vendor');
    const customer_element = document.getElementById('transfer_to_customer');

    toggleElements();
    
    radios.forEach(function(radio) {
      radio.addEventListener('change', function() {
        toggleElements();
      });
    });

    /**
     * Toggles the visibility of elements based on the selected recipient type.
     */
    function toggleElements() {
      const userType = document.querySelector('input[name="recipient_type"]:checked').value;
      vendor_element.classList.toggle('hidden', userType !== 'vendor');
      customer_element.classList.toggle('hidden', userType !== 'customer');
      
      parent.removeChild(userType === 'customer' ?  vendor_element : customer_element);
      parent.insertBefore(userType === 'customer' ? customer_element : vendor_element, parent.firstChild);
    }
  </script> <!-- Modal script-->
</div> <!--  .modal-dialog -->
