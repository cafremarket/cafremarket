@if (config('app.demo') == true)
  <hr />
  <div class="text-left mt-3 ml-4">
    <h4>Demo Affiliate:: <button class="btn btn-primary btn-sm" id="affiliate-demo">Login</button></h4>
    <p class="my-2">Username: <strong>affiliate@demo.com</strong> | Password: <strong>123456</strong></p>
  </div>

  <script>
    document.getElementById('affiliate-demo').addEventListener("click", function() {
      document.getElementById('email').value = 'affiliate@demo.com';
      document.getElementById('password').value = '123456';
      document.getElementById('loginForm-1').submit();
    });
  </script>
@endif
