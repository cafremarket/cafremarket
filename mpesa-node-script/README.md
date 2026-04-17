# M-Pesa Mozambique – Node.js (mpesa-mz-nodejs-lib)

Uses **[mpesa-mz-nodejs-lib](https://www.npmjs.com/package/mpesa-mz-nodejs-lib)** – Node.js library for the M-Pesa Mozambique API (port of [mpesa-php-api](https://github.com/abdulmueid/mpesa-php-api)).

## Install

```bash
npm install
```

## Config

Create `.env` from `.env.example` and set values from the [M-Pesa Developer Portal](https://developer.mpesa.vm.co.mz):

- `public_key` – Public key (raw base64 or PEM)
- `api_host` – e.g. `api.sandbox.vm.co.mz`
- `api_key` – API key
- `origin` – e.g. `developer.mpesa.vm.co.mz`
- `service_provider_code` – e.g. `171717`
- `initiator_identifier` – Initiator Identifier
- `security_credential` – Security Credential

## Run

```bash
npm start
```

Runs a C2B transaction with test MSISDN and amount from `.env`.

## Usage in your code

```js
const Transaction = require('mpesa-mz-nodejs-lib');

var config = {
  public_key: '<Public key>',
  api_host: 'api.sandbox.vm.co.mz',
  api_key: '<API key>',
  origin: '<Origin>',
  service_provider_code: '<Service provider code>',
  initiator_identifier: '<Initiator Identifier>',
  security_credential: '<Security Credential>',
};

const transaction = new Transaction(config);

// C2B
transaction
  .c2b({
    amount: 10,
    msisdn: '258843330333',
    reference: 'T12344C',
    third_party_reference: 'ref1',
  })
  .then((response) => console.log(response))
  .catch((error) => console.log(error));

// Query status
transaction
  .query({
    query_reference: '<Transaction reference>',
    third_party_reference: '<Third-party reference>',
  })
  .then((response) => console.log(response))
  .catch((error) => console.log(error));

// Reversal
transaction
  .reverse({
    amount: 10,
    transaction_id: '<Transaction ID>',
    third_party_reference: '<Third-party reference>',
  })
  .then((response) => console.log(response))
  .catch((error) => console.log(error));
```

## Response (example)

```json
{
  "output_ResponseCode": "INS-0",
  "output_ResponseDesc": "Request processed successfully",
  "output_ResponseTransactionStatus": "Completed",
  "output_ConversationID": "3b46f68931324acb857ae4fe52b826b5",
  "output_ThirdPartyReference": "XXXXX"
}
```

## Features (mpesa-mz-nodejs-lib)

- C2B, B2C, transaction query, reversal
- Promise-based, parameter validation
- Docs: [library documentation](https://ivanruby.github.io/mpesa-mz-nodejs-lib/)
