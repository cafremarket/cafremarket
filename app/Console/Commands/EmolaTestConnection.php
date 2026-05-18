<?php

namespace App\Console\Commands;

use App\Services\Emola\EmolaClient;
use Illuminate\Console\Command;

class EmolaTestConnection extends Command
{
    protected $signature = 'emola:test {--balance : Run queryAccountBalance}';

    protected $description = 'Test eMola SOAP gateway connectivity (WSDL + gwOperation)';

    public function handle(EmolaClient $client): int
    {
        $this->info('WSDL: '.config('emola.wsdl'));
        $this->info('Endpoint: '.config('emola.endpoint'));

        if (config('emola.fake')) {
            $this->warn('EMOLA_FAKE=true — SOAP calls are stubbed; set EMOLA_FAKE=false on production.');
        }

        try {
            if ($this->option('balance')) {
                $res = $client->queryAccountBalance();
            } else {
                $res = $client->pushUssdQueryTrans($client->generateTransId(), 'C2B');
            }

            $this->table(
                ['Field', 'Value'],
                [
                    ['gateway_error', $res->gatewayError],
                    ['gateway_description', $res->gatewayDescription ?? '—'],
                    ['gwtransid', $res->gwtransid ?? '—'],
                    ['ok', $res->ok() ? 'yes' : 'no'],
                ]
            );

            if ($res->originalData) {
                $this->line('Original data:');
                $this->line(json_encode($res->originalData, JSON_PRETTY_PRINT));
            }

            if ($res->gatewayError === 'SOAP_FAULT') {
                $this->newLine();
                $this->warn('Cannot reach the eMola gateway. Run this on the production server (VPN to 10.229.16.29) or set EMOLA_FAKE=true for local UI testing.');
            }

            return $res->ok() ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
