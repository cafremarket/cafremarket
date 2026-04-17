/**
 * M-Pesa Mozambique (Vodacom) using mpesa-mz-nodejs-lib
 * https://www.npmjs.com/package/mpesa-mz-nodejs-lib
 *
 * Config from .env: public_key, api_host, api_key, origin, service_provider_code,
 *                   initiator_identifier, security_credential
 */

require('dotenv').config();

const Transaction = require('mpesa-mz-nodejs-lib');

const config = {
  public_key: process.env.MPESA_PUBLIC_KEY || process.env.MPESA_MZ_PUBLIC_KEY || '',
  api_host: process.env.MPESA_API_HOST || process.env.MPESA_MZ_API_HOST || 'api.sandbox.vm.co.mz',
  api_key: process.env.MPESA_API_KEY || process.env.MPESA_MZ_API_KEY || '',
  origin: process.env.MPESA_ORIGIN || process.env.MPESA_MZ_CORS_ORIGIN || 'developer.mpesa.vm.co.mz',
  service_provider_code: process.env.MPESA_SERVICE_PROVIDER_CODE || process.env.MPESA_MZ_SERVICE_PROVIDER_CODE || '171717',
  initiator_identifier: process.env.MPESA_INITIATOR_IDENTIFIER || process.env.MPESA_MZ_INITIATOR_IDENTIFIER || '',
  security_credential: process.env.MPESA_SECURITY_CREDENTIAL || process.env.MPESA_MZ_SECURITY_CREDENTIAL || '',
};

const transaction = new Transaction(config);

const TEST_MSISDN = process.env.MPESA_TEST_MSISDN || '258841485101';
const TEST_AMOUNT = Number(process.env.MPESA_TEST_AMOUNT || '10');
const TRANSACTION_REF = process.env.MPESA_TEST_TRANSACTION_REF || 'T12344C';
const THIRD_PARTY_REF = process.env.MPESA_TEST_THIRD_PARTY_REF || 'ref1';

console.log('M-Pesa Mozambique (Vodacom) – mpesa-mz-nodejs-lib');
console.log('API host:', config.api_host);
console.log('Service Provider Code:', config.service_provider_code);
console.log('');

transaction
  .c2b({
    amount: TEST_AMOUNT,
    msisdn: TEST_MSISDN,
    reference: TRANSACTION_REF,
    third_party_reference: THIRD_PARTY_REF,
  })
  .then(function (response) {
    console.log('C2B response:', response);
    if (response && (response.output_ResponseCode === 'INS-0' || response.output_ResponseCode === 0)) {
      console.log('\nSuccess. Check phone for USSD PIN prompt.');
    }
  })
  .catch(function (error) {
    console.error('C2B error:', error.message || error);
    process.exit(1);
  });
