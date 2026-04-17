<script>
  $(document).ready(function() {
    $('#js-username').on('keyup', function() {
        var username = $(this).val();
        
        @if (isset($affiliate))
            var validationUrl = '{{ route("affiliate.form.validate", ["id" => $affiliate->id]) }}';
        @else
            var validationUrl = '{{ route("affiliate.form.validate") }}';
        @endif

        $.ajax({
            url: validationUrl,
            type: 'GET',
            data: { username: username },
            success: function(response) {
                if(response.exists){
                    $('#js-username-feedback').text("{{ trans('packages.affiliate.username_is_not_available') }}").css('color', 'red');
                } else {
                    $('#js-username-feedback').text("{{ trans('packages.affiliate.username_is_available') }}").css('color', 'green');
                }
            },
            error: function() {
                $('#js-username-feedback').text('Error checking username.').css('color', 'red');
            }
        });
    });
  });
</script>