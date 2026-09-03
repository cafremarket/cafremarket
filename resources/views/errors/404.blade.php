<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>{{ trans('responses.page_not_found') }} — {{ config('app.name', 'Cafrepay') }}</title>
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
    $isSelling = str_starts_with($path, '/selling');

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
      } elseif ($isSelling && \Illuminate\Support\Facades\Route::has('selling')) {
        $homeUrl = route('selling');
        $homeLabel = trans('responses.go_to_homepage');
      } elseif (\Illuminate\Support\Facades\Route::has('homepage')) {
        $homeUrl = route('homepage');
      }
    } catch (\Throwable $e) {
      $homeUrl = url('/');
    }

    $previous = url()->previous();
    $canGoBack = $previous
      && $previous !== url()->current()
      && ! str_contains($previous, '/404');
  @endphp
  <style>
    :root {
      --bg-1: #0f1c18;
      --bg-2: #1a332b;
      --bg-3: #243f36;
      --accent: #3dd68c;
      --accent-soft: rgba(61, 214, 140, 0.16);
      --text: #f4f7f5;
      --muted: #a8b8b1;
      --card: rgba(255, 255, 255, 0.06);
      --border: rgba(255, 255, 255, 0.1);
      --shadow: 0 30px 80px rgba(0, 0, 0, 0.35);
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    html, body {
      min-height: 100%;
    }

    body {
      font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
      color: var(--text);
      background:
        radial-gradient(ellipse 80% 60% at 15% 10%, rgba(61, 214, 140, 0.18), transparent 55%),
        radial-gradient(ellipse 70% 50% at 90% 85%, rgba(56, 156, 120, 0.2), transparent 50%),
        linear-gradient(160deg, var(--bg-1), var(--bg-2) 45%, var(--bg-3));
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 20px;
      overflow-x: hidden;
    }

    .nf {
      width: 100%;
      max-width: 560px;
      text-align: center;
      animation: nf-rise 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    .nf__brand {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      margin-bottom: 36px;
      text-decoration: none;
      color: var(--text);
      transition: opacity 0.2s ease;
    }

    .nf__brand:hover { opacity: 0.85; }

    .nf__logo {
      max-height: 44px;
      max-width: 160px;
      object-fit: contain;
    }

    .nf__brand-name {
      font-size: 1.25rem;
      font-weight: 700;
      letter-spacing: -0.02em;
    }

    .nf__card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 28px;
      padding: 48px 36px 40px;
      backdrop-filter: blur(18px);
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }

    .nf__card::before {
      content: "";
      position: absolute;
      inset: -40% auto auto -20%;
      width: 220px;
      height: 220px;
      border-radius: 50%;
      background: var(--accent-soft);
      filter: blur(24px);
      animation: nf-glow 4.5s ease-in-out infinite alternate;
      pointer-events: none;
    }

    .nf__code {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: clamp(4.5rem, 16vw, 7rem);
      font-weight: 800;
      letter-spacing: -0.06em;
      line-height: 1;
      background: linear-gradient(180deg, #fff 10%, var(--accent) 100%);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      margin-bottom: 12px;
      position: relative;
      animation: nf-pulse 3.2s ease-in-out infinite;
    }

    .nf__title {
      font-size: clamp(1.35rem, 3.5vw, 1.75rem);
      font-weight: 700;
      letter-spacing: -0.02em;
      margin-bottom: 12px;
      position: relative;
    }

    .nf__message {
      color: var(--muted);
      font-size: 1rem;
      line-height: 1.6;
      max-width: 36ch;
      margin: 0 auto 28px;
      position: relative;
    }

    .nf__path {
      display: inline-block;
      max-width: 100%;
      padding: 8px 14px;
      margin-bottom: 28px;
      border-radius: 999px;
      background: rgba(0, 0, 0, 0.25);
      border: 1px solid var(--border);
      color: var(--muted);
      font-size: 0.8rem;
      font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      position: relative;
    }

    .nf__actions {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      justify-content: center;
      position: relative;
    }

    .nf__btn {
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
      transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease;
    }

    .nf__btn:hover { transform: translateY(-1px); }

    .nf__btn--primary {
      background: var(--accent);
      color: #0b1a14;
      border: 1px solid transparent;
    }

    .nf__btn--primary:hover {
      background: #55e09d;
    }

    .nf__btn--ghost {
      background: transparent;
      color: var(--text);
      border: 1px solid var(--border);
    }

    .nf__btn--ghost:hover {
      border-color: rgba(255, 255, 255, 0.28);
      background: rgba(255, 255, 255, 0.05);
    }

    .nf__hint {
      margin-top: 28px;
      color: rgba(168, 184, 177, 0.75);
      font-size: 0.85rem;
      position: relative;
    }

    @keyframes nf-rise {
      from { opacity: 0; transform: translateY(18px) scale(0.98); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes nf-glow {
      from { transform: translate(0, 0); opacity: 0.55; }
      to { transform: translate(40px, 30px); opacity: 1; }
    }

    @keyframes nf-pulse {
      0%, 100% { filter: drop-shadow(0 0 0 transparent); }
      50% { filter: drop-shadow(0 0 18px rgba(61, 214, 140, 0.35)); }
    }

    @media (max-width: 480px) {
      .nf__card { padding: 36px 22px 32px; border-radius: 22px; }
      .nf__actions { flex-direction: column; }
      .nf__btn { width: 100%; }
    }
  </style>
</head>
<body>
  <main class="nf" role="main">
    <a class="nf__brand" href="{{ $homeUrl }}">
      @if ($logoUrl)
        <img class="nf__logo" src="{{ $logoUrl }}" alt="{{ $brandName }}">
      @else
        <span class="nf__brand-name">{{ $brandName }}</span>
      @endif
    </a>

    <section class="nf__card" aria-labelledby="nf-title">
      <div class="nf__code" aria-hidden="true">404</div>
      <h1 id="nf-title" class="nf__title">{{ trans('responses.page_not_found') }}</h1>
      <p class="nf__message">{{ trans('responses.404_not_found') }}</p>

      @if ($path && $path !== '/')
        <div class="nf__path" title="{{ $path }}">{{ $path }}</div>
      @endif

      <div class="nf__actions">
        <a class="nf__btn nf__btn--primary" href="{{ $homeUrl }}">{{ $homeLabel }}</a>
        @if ($canGoBack)
          <a class="nf__btn nf__btn--ghost" href="{{ $previous }}">{{ trans('theme.button.go_back') }}</a>
        @endif
      </div>

      <p class="nf__hint">{{ trans('responses.404_hint') }}</p>
    </section>
  </main>
</body>
</html>
