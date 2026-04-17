# M-Pesa Mozambique (Vodacom)

C2B payment integration for **M-Pesa Mozambique** (Vodacom). Uses **encrypted API key as Bearer** (no getSession).

## Env configuration

Use these in your `.env` (from developer.mpesa.vm.co.mz, activated by Cafre-Pay, Su, Lda.):

```env
MPESA_COUNTRY=MZ
MPESA_MZ_API_KEY=your_api_key
MPESA_MZ_SERVICE_PROVIDER_CODE=171717
MPESA_MZ_INITIATOR_IDENTIFIER=apiuser
MPESA_MZ_SECURITY_CREDENTIAL=your_security_credential
MPESA_MZ_SANDBOX=true
MPESA_MZ_CORS_ORIGIN=developer.mpesa.vm.co.mz
MPESA_MZ_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
...your platform public key...
-----END PUBLIC KEY-----"
MPESA_MZ_BASE_URL=https://api.sandbox.vm.co.mz:18352
MPESA_MZ_PATH_PREFIX=/ipg/v1x
MPESA_MZ_ORIGIN=developer.mpesa.vm.co.mz
# Optional: query uses port 18353 (default: derived from base_url)
# MPESA_MZ_QUERY_BASE_URL=https://api.sandbox.vm.co.mz:18353
# On production if your server cannot reach port 18353, set to false; confirmation will use callback only:
# MPESA_QUERY_ENABLED=false
```

- **Production:** set `MPESA_MZ_SANDBOX=false` and use production base URL (e.g. `https://api.vm.co.mz:18352` or as provided by Vodacom).

## Phone numbers

Customer numbers are normalized to Mozambique format `258XXXXXXXXX` (e.g. 84 123 4567 → 258841234567).

## Callback URL

Register in the M-Pesa portal: `https://yourdomain.com/payment/response/callback`.

- **On server (production):** Set `APP_URL` in `.env` to your public URL (e.g. `https://yourdomain.com`). Optionally set `MPESA_CALLBACK_BASE_URL` to the same value so M-Pesa can send the payment callback to your server. If the callback URL is wrong (e.g. still localhost), you will stay on "waiting for payment" because the callback never reaches your app.
- **Sandbox with ngrok:** Set `MPESA_CALLBACK_BASE_URL` or `API_TEST_REDIRECT_URL` (when `APP_ENV=local`) to your ngrok URL.

## API

- **Auth:** `Authorization: Bearer {encrypted_api_key}` (API key encrypted with platform public key).
- **Base URL:** from `MPESA_MZ_BASE_URL` (e.g. `https://api.sandbox.vm.co.mz:18352`).
- **Path prefix:** `MPESA_MZ_PATH_PREFIX` (e.g. `/ipg/v1x`).
- **Endpoints:** `{path_prefix}/c2bPayment/singleStage`, `{path_prefix}/queryTransactionStatus`.

## HTTP 0 / "M-Pesa Mozambique error: (HTTP 0)"

If the **Query Transaction Status** call logs **HTTP 0** and an empty response, the request is sent but **no response is received** from the M-Pesa API. Common causes:

1. **Outbound connection blocked** – Server or firewall does not allow outbound HTTPS to `api.sandbox.vm.co.mz` (or production host) on **port 18353**. Ask your host to allow outbound TCP to that host:port.
2. **SSL/TLS failure** – Server cannot verify the M-Pesa API certificate (old CA bundle, corporate proxy). As a **temporary test only**, set in `.env`: `MPESA_MZ_SSL_VERIFY=false`. Prefer fixing the server’s CA bundle or PHP/cURL SSL config.
3. **DNS** – Server cannot resolve `api.sandbox.vm.co.mz`. Test from the server: `curl -v https://api.sandbox.vm.co.mz:18353`.
4. **Timeout** – Request times out before the API responds; the code uses 15s connect and 30s total timeout.

After fixing, clear config cache: `php artisan config:clear`.

## 403 Forbidden / "Request forbidden by administrative rules"

If you see **403** with an HTML page (e.g. Incapsula), the request is being blocked by a firewall/WAF in front of the M-Pesa API, not by your code. Common causes:

1. **IP whitelist** – The sandbox may only allow requests from IPs registered in the M-Pesa Mozambique developer portal. Ask Vodacom / Cafre-Pay to whitelist your **server’s public IP** (or your office IP if testing from localhost).
2. **Local testing** – From `localhost` your outbound IP is your ISP’s; that IP must be whitelisted, or test from a server that is already whitelisted.
3. **User-Agent** – This integration sends a clear `User-Agent`; if the portal requires a specific value, it can be added via config.

## Stuck on "Waiting for payment" on the server

If wallet or order payment works on localhost but on the **server** the page never updates after the customer pays:

1. **Callback URL** – Ensure `APP_URL` (and optionally `MPESA_CALLBACK_BASE_URL`) in `.env` on the server is your **public** URL (e.g. `https://yourdomain.com`), not `http://localhost`. M-Pesa must be able to POST to `{that_url}/payment/response/callback`.
2. **Firewall** – Allow incoming POST requests to `/payment/response/callback` (no auth).
3. **Query API unreachable (connection refused on port 18353)** – Many production hosts block outbound port 18353. C2B payment uses port **18352** (which may work); status polling uses **18353**. If you see "Connection refused" or "HTTP 0" for the query in logs, add to `.env` on the **server only**:
   ```env
   MPESA_QUERY_ENABLED=false
   ```
   Then confirmation relies **only on the callback**. When the customer pays, M-Pesa POSTs to your callback URL; the order/wallet is updated and the next poll (or refresh) will show success. No need for the server to reach port 18353.
4. **Query API 403** – If the status poll uses the M-Pesa "query transaction" API and your server gets 403, Vodacom may need to whitelist your **server’s public IP** for the query endpoint. Alternatively use `MPESA_QUERY_ENABLED=false` as above.
5. **Manual check** – On the waiting page, the customer can click **"I've paid – check status"** to trigger an immediate status check (with `force=1`), which can help if the callback was delayed or the poll had failed.
