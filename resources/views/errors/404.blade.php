<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>{{ trans('responses.page_not_found') }} — {{ config('app.name', 'Cafrepay') }}</title>
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
        radial-gradient(ellipse 70% 55% at 12% 8%, rgba(255, 102, 0, 0.14), transparent 55%),
        radial-gradient(ellipse 55% 45% at 92% 88%, rgba(255, 148, 77, 0.12), transparent 50%),
        linear-gradient(165deg, #fffaf6 0%, #f8fafc 42%, #eef2f7 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 20px;
      overflow-x: hidden;
    }

    .nf {
      width: 100%;
      max-width: 540px;
      text-align: center;
      animation: nf-rise 0.65s cubic-bezier(0.22, 1, 0.36, 1) both;
    }

    .nf__brand {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      margin-bottom: 32px;
      text-decoration: none;
      color: var(--primary);
      transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .nf__brand:hover {
      opacity: 0.88;
      transform: translateY(-1px);
    }

    .nf__logo {
      max-height: 48px;
      max-width: 180px;
      object-fit: contain;
    }

    .nf__brand-name {
      font-size: 1.5rem;
      font-weight: 800;
      letter-spacing: -0.03em;
      line-height: 1.1;
    }

    .nf__card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 48px 36px 40px;
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }

    .nf__card::before {
      content: "";
      position: absolute;
      top: -80px;
      right: -60px;
      width: 200px;
      height: 200px;
      border-radius: 50%;
      background: var(--primary-soft);
      filter: blur(8px);
      animation: nf-drift 5s ease-in-out infinite alternate;
      pointer-events: none;
    }

    .nf__code {
      display: inline-block;
      font-size: clamp(4.25rem, 15vw, 6.5rem);
      font-weight: 800;
      letter-spacing: -0.07em;
      line-height: 1;
      color: var(--primary);
      margin-bottom: 10px;
      position: relative;
      animation: nf-pop 0.8s cubic-bezier(0.22, 1, 0.36, 1) 0.15s both;
    }

    .nf__title {
      font-size: clamp(1.35rem, 3.5vw, 1.7rem);
      font-weight: 700;
      letter-spacing: -0.025em;
      margin-bottom: 12px;
      position: relative;
    }

    .nf__message {
      color: var(--muted);
      font-size: 1rem;
      line-height: 1.65;
      max-width: 36ch;
      margin: 0 auto 28px;
      position: relative;
    }

    .nf__path {
      display: inline-block;
      max-width: 100%;
      padding: 8px 14px;
      margin-bottom: 28px;
      border-radius: 10px;
      background: #f8fafc;
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
      transition: transform 0.18s ease, background 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .nf__btn:hover { transform: translateY(-1px); }

    .nf__btn--primary {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: #fff;
      border: 1px solid transparent;
      box-shadow: 0 8px 20px rgba(255, 102, 0, 0.28);
    }

    .nf__btn--primary:hover {
      box-shadow: 0 10px 24px rgba(255, 102, 0, 0.36);
    }

    .nf__btn--ghost {
      background: #fff;
      color: var(--text);
      border: 1px solid var(--border);
    }

    .nf__btn--ghost:hover {
      border-color: #cbd5e1;
      background: #f8fafc;
    }

    .nf__hint {
      margin-top: 28px;
      color: #94a3b8;
      font-size: 0.85rem;
      position: relative;
    }

    @keyframes nf-rise {
      from { opacity: 0; transform: translateY(18px) scale(0.98); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes nf-drift {
      from { transform: translate(0, 0); opacity: 0.7; }
      to { transform: translate(-24px, 18px); opacity: 1; }
    }

    @keyframes nf-pop {
      from { opacity: 0; transform: scale(0.86); }
      to { opacity: 1; transform: scale(1); }
    }

    @media (max-width: 480px) {
      .nf__card { padding: 36px 22px 32px; border-radius: 20px; }
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
