<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nearby Stores Diagnostic</title>
  <style>
    :root {
      --bg: #f8fafc;
      --card: #fff;
      --border: #e2e8f0;
      --text: #0f172a;
      --muted: #64748b;
      --ok: #166534;
      --ok-bg: #dcfce7;
      --bad: #991b1b;
      --bad-bg: #fee2e2;
      --warn: #92400e;
      --warn-bg: #fef3c7;
      --primary: #2563eb;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Inter, system-ui, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.5;
    }
    .wrap { max-width: 1400px; margin: 0 auto; padding: 24px; }
    h1 { margin: 0 0 8px; font-size: 28px; }
    .sub { color: var(--muted); margin-bottom: 24px; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 24px; }
    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 16px;
    }
    .card strong { display: block; font-size: 24px; }
    .card span { color: var(--muted); font-size: 13px; }
    form.card { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; align-items: end; }
    label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .04em; }
    input {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 14px;
    }
    button, .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 10px 16px;
      border-radius: 8px;
      border: none;
      background: var(--primary);
      color: #fff;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      font-size: 14px;
    }
    .btn-outline { background: #fff; color: var(--primary); border: 1px solid var(--border); }
    table { width: 100%; border-collapse: collapse; background: var(--card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
    th, td { padding: 12px 14px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: top; font-size: 13px; }
    th { background: #f1f5f9; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
    tr:last-child td { border-bottom: none; }
    .badge {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
    }
    .badge-ok { background: var(--ok-bg); color: var(--ok); }
    .badge-bad { background: var(--bad-bg); color: var(--bad); }
    .badge-warn { background: var(--warn-bg); color: var(--warn); }
    .issues { margin: 0; padding-left: 18px; color: var(--bad); }
    .issues li { margin-bottom: 4px; }
    .toolbar { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
    .note { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e3a8a; padding: 12px 14px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
    @media (max-width: 900px) {
      table { display: block; overflow-x: auto; white-space: nowrap; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Nearby Stores Diagnostic</h1>
    <p class="sub">Check every store, its distance from your location, and why it is or is not shown in Nearby.</p>

    <div class="note">
      Hyperlocal: <strong>{{ $hyperlocalEnabled ? 'Enabled' : 'Disabled' }}</strong>.
      Buyer search radius: <strong>{{ number_format($radiusKm, 1) }} km</strong>.
      Default shop service radius: <strong>{{ number_format($report['default_shop_radius_km'], 1) }} km</strong>.
      @if ($latitude && $longitude)
        Your test location: <strong>{{ number_format($latitude, 6) }}, {{ number_format($longitude, 6) }}</strong>
        @if ($addressText) — {{ $addressText }} @endif
      @else
        <strong>No buyer location set.</strong> Enter coordinates below or set location on the storefront first.
      @endif
    </div>

    <form method="get" action="{{ route('test.nearby_stores') }}" class="card">
      <div>
        <label for="lat">Latitude</label>
        <input type="text" name="lat" id="lat" value="{{ $latitude }}" placeholder="-25.9655">
      </div>
      <div>
        <label for="lng">Longitude</label>
        <input type="text" name="lng" id="lng" value="{{ $longitude }}" placeholder="32.5832">
      </div>
      <div>
        <label for="radius">Search radius (km)</label>
        <input type="number" step="0.1" min="1" name="radius" id="radius" value="{{ $radiusKm }}">
      </div>
      <div>
        <label for="address_text">Address label (optional)</label>
        <input type="text" name="address_text" id="address_text" value="{{ $addressText }}" placeholder="Test location">
      </div>
      <div>
        <button type="submit">Run test</button>
      </div>
      <div>
        <button type="button" class="btn-outline" id="use-current-location">Use my location</button>
      </div>
    </form>

    <div class="grid">
      <div class="card"><strong>{{ $report['summary']['total'] }}</strong><span>Total stores</span></div>
      <div class="card"><strong>{{ $report['summary']['showing_nearby'] }}</strong><span>Showing in nearby</span></div>
      <div class="card"><strong>{{ $report['summary']['with_location'] }}</strong><span>With map location</span></div>
      <div class="card"><strong>{{ $report['summary']['with_inventory'] }}</strong><span>With active products</span></div>
      <div class="card"><strong>{{ $report['summary']['active_scope'] }}</strong><span>Pass active scope</span></div>
    </div>

    <div class="toolbar">
      <a href="{{ url('/') }}" class="btn btn-outline">Back to storefront</a>
      <a href="{{ route('shops', ['lat' => $latitude, 'lng' => $longitude]) }}" class="btn btn-outline">Open nearby shops page</a>
    </div>

    <table>
      <thead>
        <tr>
          <th>Store</th>
          <th>Status</th>
          <th>Location</th>
          <th>Distance</th>
          <th>Inventory</th>
          <th>Radius</th>
          <th>Nearby</th>
          <th>Issues</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($report['shops'] as $shop)
          <tr>
            <td>
              <strong>{{ $shop['name'] }}</strong><br>
              <span style="color:var(--muted)">#{{ $shop['id'] }} · {{ $shop['slug'] }}</span>
            </td>
            <td>
              <span class="badge {{ $shop['active'] ? 'badge-ok' : 'badge-bad' }}">{{ $shop['active'] ? 'Active' : 'Inactive' }}</span><br>
              <span class="badge {{ $shop['verified'] ? 'badge-ok' : 'badge-warn' }}" style="margin-top:6px">{{ $shop['verified'] ? 'Verified' : 'Not verified' }}</span><br>
              <span class="badge {{ $shop['passes_active_scope'] ? 'badge-ok' : 'badge-bad' }}" style="margin-top:6px">{{ $shop['passes_active_scope'] ? 'Live scope OK' : 'Not live' }}</span>
            </td>
            <td>
              @if ($shop['has_location'])
                {{ $shop['address_line'] }}{{ $shop['city'] ? ', '.$shop['city'] : '' }}<br>
                <span style="color:var(--muted)">{{ number_format($shop['latitude'], 6) }}, {{ number_format($shop['longitude'], 6) }}</span>
              @else
                <span class="badge badge-bad">No location</span>
              @endif
            </td>
            <td>
              @if ($shop['distance_km'] !== null)
                <strong>{{ number_format($shop['distance_km'], 2) }} km</strong><br>
                <span style="color:var(--muted)">
                  Buyer: {{ $shop['within_buyer_radius'] ? 'Yes' : 'No' }}<br>
                  Shop: {{ $shop['within_shop_radius'] ? 'Yes' : 'No' }}
                </span>
              @else
                —
              @endif
            </td>
            <td>{{ $shop['active_inventories_count'] }}</td>
            <td>{{ number_format($shop['service_radius_km'], 1) }} km</td>
            <td>
              @if ($shop['shows_in_nearby'])
                <span class="badge badge-ok">Visible</span>
              @else
                <span class="badge badge-bad">Hidden</span>
              @endif
            </td>
            <td>
              @if (count($shop['issues']))
                <ul class="issues">
                  @foreach ($shop['issues'] as $issue)
                    <li>{{ $issue }}</li>
                  @endforeach
                </ul>
              @else
                <span style="color:var(--ok)">No issues</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8">No stores found in the database.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <script>
    document.getElementById('use-current-location')?.addEventListener('click', function() {
      if (!navigator.geolocation) {
        alert('Geolocation is not supported in this browser.');
        return;
      }
      navigator.geolocation.getCurrentPosition(function(pos) {
        document.getElementById('lat').value = pos.coords.latitude;
        document.getElementById('lng').value = pos.coords.longitude;
      }, function() {
        alert('Could not get your location. Please allow location access.');
      }, { enableHighAccuracy: true, timeout: 15000 });
    });
  </script>
</body>
</html>
