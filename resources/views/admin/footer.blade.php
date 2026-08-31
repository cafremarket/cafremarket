<!-- Main Footer -->
<footer class="main-footer">
  <div class="pull-right hidden-xs">
    @if (auth()->guard('web')->check() && auth()->user()->isSuperAdmin())
      <a href="https://cafremarket.co.mz/" target="_blank" style="color:#6366f1;font-weight:500;">
        Cafremarket v{{ \App\Models\System::VERSION }}
      </a>
    @else
      <span>{{ trans('app.today_is') . ' ' . date('l M j, Y') }}</span>
    @endif
  </div>
  <strong>Copyright &copy; {{ date('Y') }} {{ config('system_settings.name') ?? config('app.name') }}.</strong> All rights reserved.
</footer>
