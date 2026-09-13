<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>{{ trans('messages.permission.denied') }} — {{ config('app.name', 'Cafrepay') }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  @php
    $brandName = config('app.name', 'Cafrepay');
    try {
      if (function_exists('get_platform_title')) {
        $brandName = get_platform_title() ?: $brandName;
      }
    } catch (\Throwable $e) {
      // Platform settings may be unavailable during hard failures.
    }

    $logoUrl = null;
    try {
      if (function_exists('get_logo_url')) {
        $logoUrl = get_logo_url('system', 'full');
      }
    } catch (\Throwable $e) {
      $logoUrl = null;
    }

    $path = '/'.ltrim(request()->path(), '/');
    $isMerchant = str_starts_with($path, '/merchant');
    $isAdmin = str_starts_with($path, '/admin');

    $homeUrl = url('/');
    $homeLabel = trans('responses.go_to_homepage');

    try {
      if ($isMerchant && \Illuminate\Support\Facades\Route::has('merchant.dashboard')) {
        $homeUrl = route('merchant.dashboard');
        $homeLabel = trans('responses.go_to_dashboard');
      } elseif ($isAdmin && \Illuminate\Support\Facades\Route::has('admin.admin.dashboard')) {
        $homeUrl = route('admin.admin.dashboard');
        $homeLabel = trans('responses.go_to_dashboard');
      } elseif ($isAdmin) {
        $homeUrl = url('/admin/dashboard');
        $homeLabel = trans('responses.go_to_dashboard');
      } elseif (\Illuminate\Support\Facades\Route::has('admin.admin.dashboard')) {
        // Authorizable permission checks default to the admin panel.
        $homeUrl = route('admin.admin.dashboard');
        $homeLabel = trans('responses.go_to_dashboard');
      } elseif (\Illuminate\Support\Facades\Route::has('homepage')) {
        $homeUrl = route('homepage');
      }
    } catch (\Throwable $e) {
      $homeUrl = url('/');
    }

    $previous = url()->previous();
    $canGoBack = $previous
      && $previous !== url()->current()
      && ! str_contains($previous, '/forbidden');
  @endphp
  <style>
    :root {
      --primary: #ff6600;
      --primary-dark: #cc5200;
      --primary-soft: rgba(255, 102, 0, 0.12);
      --text: #1e293b;
      --muted: #64748b;
      --border: #e8edf2;
      --card: #ffffff;
      --shadow: 0 20px 50px rgba(15, 23, 42, 0.08), 0 4px 12px rgba(15, 23, 42, 0.04);
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    html, body { min-height: 100%; }

    body {
      font-family: "Outfit", "Segoe UI", sans-serif;
      color: var(--text);
      background:
        radial-gradient(ellipse 70% 55% at 88% 10%, rgba(255, 102, 0, 0.13), transparent 55%),
        radial-gradient(ellipse 50% 40% at 8% 90%, rgba(255, 148, 77, 0.1), transparent 50%),
        linear-gradient(165deg, #fffaf6 0%, #f8fafc 42%, #eef2f7 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 20px;
      overflow-x: hidden;
    }

    .pd {
      width: 100%;
      max-width: 540px;
      text-align: center;
      animation: pd-rise 0.65s cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    .pd__brand {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      margin-bottom: 32px;
      text-decoration: none;
      color: var(--primary);
      transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .pd__brand:hover {
      opacity: 0.88;
      transform: translateY(-1px);
    }

    .pd__logo {
      max-height: 48px;
      max-width: 180px;
      object-fit: contain;
    }

    .pd__brand-name {
      font-size: 1.5rem;
      font-weight: 800;
      letter-spacing: -0.03em;
      line-height: 1.1;
    }

    .pd__card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 48px 36px 40px;
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }

    .pd__card::before {
      content: "";
      position: absolute;
      top: -70px;
      left: -50px;
      width: 180px;
      height: 180px;
      border-radius: 50%;
      background: var(--primary-soft);
      filter: blur(8px);
      animation: pd-drift 5s ease-in-out infinite alternate;
      pointer-events: none;
    }

    .pd__icon {
      width: 72px;
      height: 72px;
      margin: 0 auto 22px;
      border-radius: 20px;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 12px 28px rgba(255, 102, 0, 0.3);
      position: relative;
      animation: pd-pop 0.75s cubic-bezier(0.22, 1, 0.36, 1) 0.12s both;
    }

    .pd__icon svg {
      width: 34px;
      height: 34px;
      fill: none;
      stroke: currentColor;
      stroke-width: 2;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .pd__eyebrow {
      display: inline-block;
      margin-bottom: 10px;
      color: var(--primary);
      font-size: 0.8rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      position: relative;
    }

    .pd__title {
      font-size: clamp(1.35rem, 3.5vw, 1.7rem);
      font-weight: 700;
      letter-spacing: -0.025em;
      margin-bottom: 12px;
      position: relative;
    }

    .pd__message {
      color: var(--muted);
      font-size: 1rem;
      line-height: 1.65;
      max-width: 38ch;
      margin: 0 auto 28px;
      position: relative;
    }

    .pd__actions {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      justify-content: center;
      position: relative;
    }

    .pd__btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      min-height: 46px;
      padding: 0 22px;
      border-radius: 12px;
      font-size: 0.95rem;
      font-weight: 600;
      text-decoration: none;
      transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .pd__btn:hover { transform: translateY(-1px); }

    .pd__btn--primary {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: #fff;
      border: 1px solid transparent;
      box-shadow: 0 8px 20px rgba(255, 102, 0, 0.28);
    }

    .pd__btn--primary:hover {
      box-shadow: 0 10px 24px rgba(255, 102, 0, 0.36);
    }

    .pd__btn--ghost {
      background: #fff;
      color: var(--text);
      border: 1px solid var(--border);
    }

    .pd__btn--ghost:hover {
      border-color: #cbd5e1;
      background: #f8fafc;
    }

    .pd__hint {
      margin-top: 28px;
      color: #94a3b8;
      font-size: 0.85rem;
      position: relative;
    }

    @keyframes pd-rise {
      from { opacity: 0; transform: translateY(18px) scale(0.98); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes pd-drift {
      from { transform: translate(0, 0); opacity: 0.7; }
      to { transform: translate(22px, 16px); opacity: 1; }
    }

    @keyframes pd-pop {
      from { opacity: 0; transform: scale(0.8) rotate(-6deg); }
      to { opacity: 1; transform: scale(1) rotate(0deg); }
    }

    @media (max-width: 480px) {
      .pd__card { padding: 36px 22px 32px; border-radius: 20px; }
      .pd__actions { flex-direction: column; }
      .pd__btn { width: 100%; }
    }
  </style>
</head>
<body>
  <main class="pd" role="main">
    <a class="pd__brand" href="{{ $homeUrl }}">
      @if ($logoUrl)
        <img class="pd__logo" src="{{ $logoUrl }}" alt="{{ $brandName }}">
      @else
        <span class="pd__brand-name">{{ $brandName }}</span>
      @endif
    </a>

    <section class="pd__card" aria-labelledby="pd-title">
      <div class="pd__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24">
          <rect x="5" y="11" width="14" height="10" rx="2"></rect>
          <path d="M8 11V7a4 4 0 0 1 8 0v4"></path>
        </svg>
      </div>

      <div class="pd__eyebrow">403</div>
      <h1 id="pd-title" class="pd__title">{{ trans('messages.permission.denied') }}</h1>
      <p class="pd__message">{{ trans('responses.permission_denied_message') }}</p>

      <div class="pd__actions">
        <a class="pd__btn pd__btn--primary" href="{{ $homeUrl }}">{{ $homeLabel }}</a>
        @if ($canGoBack)
          <a class="pd__btn pd__btn--ghost" href="{{ $previous }}">{{ trans('theme.button.go_back') }}</a>
        @endif
      </div>

      <p class="pd__hint">{{ trans('responses.permission_denied_hint') }}</p>
    </section>
  </main>
</body>
</html>
