<?php

namespace Incevio\Package\Wallet\Models;

use App\Models\Customer;
use App\Models\PdfTemplate;
use App\Models\Shop;
use App\Services\PdfGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Incevio\Package\Wallet\Interfaces\Mathable;
use Incevio\Package\Wallet\Services\CommonService;
use Incevio\Package\Wallet\Services\WalletService;

class Transaction extends Model
{
    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_WITHDRAW = 'withdraw';

    public const TYPE_REFUND = 'refund';

    public const TYPE_PAYOUT = 'payout';

    /**
     * @var array
     */
    protected $fillable = [
        'payable_type',
        'payable_id',
        'wallet_id',
        'uuid',
        'type',
        'amount',
        'balance',
        'confirmed',
        'approved',
        'meta',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'wallet_id' => 'integer',
        'confirmed' => 'boolean',
        'approved' => 'boolean',
        'meta' => 'json',
    ];

    /**
     * {@inheritdoc}
     */
    public function getCasts(): array
    {
        return array_merge(
            parent::getCasts(),
            config('wallet.transaction.casts', [])
        );
    }

    public function getTable(): string
    {
        if (! $this->table) {
            $this->table = config('wallet.transaction.table', 'transactions');
        }

        return parent::getTable();
    }

    public function payable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(config('wallet.wallet.model', Wallet::class));
    }

    /**
     * @return int|float
     */
    public function getAmountFloatAttribute()
    {
        $decimalPlaces = app(WalletService::class)->decimalPlaces($this->wallet);

        return app(Mathable::class)->div($this->amount, $decimalPlaces);
    }

    /**
     * @return string unique ID from UUID
     */
    public function getUniqueIdAttribute()
    {
        return explode('-', $this->uuid)[0];
    }

    /**
     * @param  int|float  $amount
     */
    public function setAmountFloatAttribute($amount): void
    {
        $math = app(Mathable::class);

        $decimalPlaces = app(WalletService::class)->decimalPlaces($this->wallet);

        $this->amount = $math->round($math->mul($amount, $decimalPlaces));
    }

    /**
     * Scope a query to only include withdraw transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithdrawals($query)
    {
        return $query->where('type', 'withdraw');
    }

    /**
     *Return Type deposit
     */
    public function scopeDeposits($query)
    {
        return $query->where('type', 'deposit');
    }

    /**
     * Scope a query to only include approved transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('approved', 1);
    }

    /**
     * Scope a query to only include confirmed transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConfirmed($query)
    {
        return $query->where('confirmed', 1);
    }

    /**
     * Scope a query to only include order escrowed transactions.
     * Check the setting to pull result
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEscrowed($query)
    {
        return $query->where('confirmed', false)
            ->where('payable_type', 'App\Models\Shop')
            ->where('type', 'deposit')
            ->where('created_at', '<=', now()->subDays(get_order_escrow_holding_duration()));
    }

    /**
     * Scope a query to only include payout transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePayouts($query)
    {
        return $query->withdrawals();
    }

    /**
     * Scope a query to only include completed payout transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeComplete($query)
    {
        return $query->where('approved', 1)->orWhereNull('approved');
    }

    /**
     * Scope a query to only include pending transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('approved', 0);
    }

    /**
     * Scope a query to only include declined transactions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeclined($query)
    {
        return $query->whereNull('approved');
    }

    /**
     * Check if the transaction is type of given type
     *
     * @param  string  $type
     * @return bool
     */
    public function isTypeOf($type)
    {
        return isset($this->meta['type']) && $this->meta['type'] == $type;
    }

    /**
     * Approve the pending payout transactions.
     */
    public function approve($fee = null)
    {
        $meta['description'] = trans('packages.wallet.payout_approved');
        if ($fee && $fee > 0) {
            $meta['fee'] = $fee;
            $this->amount = ($this->amount + (-$fee));
        }

        $amount = $this->amount * -1;
        app(CommonService::class)->verifyWithdraw($this->wallet, $amount);

        $this->meta = array_merge($this->meta, $meta);
        $this->confirmed = true;
        $this->approved = true;

        DB::transaction(function () use ($amount) {
            // Charge the fee on wallet
            $this->wallet->decrement('balance', $amount);

            // Update the transaction
            $this->balance = $this->wallet->balance;
            $this->save();
        });
    }

    /**
     * decline the pending payout transactions.
     */
    public function decline()
    {
        $meta['description'] = trans('packages.wallet.payout_declined');

        $this->meta = array_merge($this->meta, $meta);
        $this->confirmed = false;
        $this->approved = null;
        $this->save();
    }

    public function isApproved()
    {
        return $this->approved == 1 && $this->confirmed == 1;
    }

    public function isDeclined()
    {
        return $this->approved === null;
    }

    /**
     * Return transaction status text.
     */
    public function statusName($plain = false)
    {
        if ($this->isApproved()) {
            $status = trans('packages.wallet.approved');
            $label = 'outline';
        } elseif ($this->isDeclined()) {
            $status = trans('packages.wallet.declined');
            $label = 'danger';
        } else {
            $status = trans('packages.wallet.pending');
            $label = 'info';
        }

        $status = strtoupper($status);

        if ($plain) {
            return $status;
        }

        return '<span class="label label-'.$label.'">'.$status.'</span>';
    }

    /**
     * Returns meta data from meta
     */
    public function getFromMetaData($attr)
    {
        return is_array($this->meta) && array_key_exists($attr, $this->meta) ? $this->meta[$attr] : '';
    }

    /**
     * Generates a PDF invoice for the transaction.
     *
     * @param  string  $action  Supported values are 'download' and 'view'. Defaults to 'download'.
     * @param  string|null  $file_path  The path to save the generated PDF file. If empty, the PDF is sent to the browser.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function invoice($action = 'download', $file_path = null)
    {
        $invoiceFrom = $this->getInvoiceFrom();
        $invoiceTo = $this->getInvoiceTo();

        $dataForInvoice = [
            'invoice_from' => $invoiceFrom,
            'invoice_to' => $invoiceTo,
            'transaction' => $this,
        ];

        $transaction_invoice_generator = new PdfGenerator;
        $pdfTemplate = PdfTemplate::where('type', PdfTemplate::TYPE_WALLET_TRANSACTION)->where('is_default', true)->first();

        return $transaction_invoice_generator->setGeneratedFileName(get_platform_title().' - '.$this->unique_id)
            ->generatePdfFromTemplate($dataForInvoice, $pdfTemplate, 'a4', $action, $file_path);
    }

    /**
     * Get the transactions invoice to
     */
    private function getInvoiceTo(): array
    {
        $address = null;
        if (isset($this->meta['from'])) {
            $shop = Shop::where('email', $this->meta['from'])->first() ?? Customer::where('email', $this->meta['from'])->first();
            $address = $shop->primaryAddress ?? $shop->address;
        } else {
            $address = $this->payable->primaryAddress ?? $this->payable->address;
        }

        $invoiceFrom = $address ? $address->toArray() : [];
        $invoiceFrom = array_values($invoiceFrom);
        array_unshift($invoiceFrom, $this->payable->getName());

        return $invoiceFrom;
    }

    /**
     * Get the transaction invoice from
     */
    private function getInvoiceFrom(): array
    {
        $invoiceTo = [];

        if (! empty($this->meta['to'])) {
            $shop = Shop::where('email', $this->meta['to'])->first() ?? Customer::where('email', $this->meta['to'])->first();
            $address = $shop->primaryAddress;
            $invoiceTo = $address ? $address->toArray() : [];

            unset($invoiceTo['address_type']);
            $invoiceTo = array_values($invoiceTo);
            array_unshift($invoiceTo, $shop->getName());
        } else {
            $invoiceTo = $this->getDefaultInvoiceAddress();
        }

        return $invoiceTo;
    }

    /**
     * Generate invoice for customer transaction
     *
     * @param  string  $action  'download'/'stream'/'save' to download/stream/save the invoice.
     * @param  string  $file_path  path to save the invoice when $action is 'save'.
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function customerInvoice($action = 'download', $file_path = null)
    {
        $invoiceFrom = $this->getInvoiceFromForCustomerInvoice();
        $invoiceTo = $this->getInvoiceToForCustomerInvoice();

        $dataForInvoice = [
            'invoice_from' => $invoiceFrom,
            'invoice_to' => $invoiceTo,
            'transaction' => $this,
        ];

        $pdfGenerator = new PdfGenerator;
        $pdfTemplate = PdfTemplate::where('type', PdfTemplate::TYPE_WALLET_TRANSACTION)->where('is_default', true)->first();

        return $pdfGenerator->setGeneratedFileName(get_platform_title().' - '.$this->unique_id)
            ->generatePdfFromTemplate($dataForInvoice, $pdfTemplate, 'a4', 'stream');
    }

    /**
     * Get invoice from address
     *
     * @return array
     */
    private function getInvoiceFromForCustomerInvoice()
    {
        $invoiceFrom = [];

        if (! empty($this->meta['from'])) {
            $customer = $this->getCustomerFromMeta('from');
            $invoiceFrom = $customer->primaryAddress->toArray() ?? [];
        } elseif (! empty($this->meta['to'])) {
            $invoiceFrom = $this->payable->primaryAddress->toArray() ?? [];
        } else {
            $invoiceFrom = $this->getDefaultInvoiceAddress();
        }

        return $invoiceFrom;
    }

    /**
     * Get invoice to address
     *
     * @return array
     */
    private function getInvoiceToForCustomerInvoice()
    {
        $invoiceTo = [];

        if (! empty($this->meta['from'])) {
            $customer = $this->getCustomerFromMeta('to');
            $invoiceTo = $customer->primaryAddress->toArray() ?? [];
        } elseif (! empty($this->meta['to'])) {
            $invoiceTo = $this->getCustomerFromMeta('to')->primaryAddress->toArray() ?? [];
        } else {
            $invoiceTo = $this->getDefaultInvoiceAddress();
        }

        return $invoiceTo;
    }

    /**
     * Get customer from meta
     *
     * @param  string  $metaKey
     * @return mixed
     */
    private function getCustomerFromMeta($metaKey)
    {
        return Customer::where('email', $this->meta[$metaKey])->first() ?? Shop::where('email', $this->meta[$metaKey])->first();
    }

    /**
     * Get default invoice address or platform name and address
     *
     * @return array
     */
    private function getDefaultInvoiceAddress()
    {
        $platform_address = multi_tag_explode([',', '<br/>'], strip_tags(get_platform_address(), '<br>'));
        $platform_address = array_filter(array_map('trim', $platform_address));
        array_unshift($platform_address, get_platform_title());
        unset($platform_address[1]);
        $platform_address = array_values($platform_address);

        return $platform_address;
    }

    /**
     * Generate a pdf invoice for the transaction.
     *
     * @param  string  $action  'download' to download , 'stream' to stream the pdf and 'save' to save to a file_path.
     * @return mixed
     */
    public function affiliateInvoice($action = 'download')
    {
        $affiliate = \Incevio\Package\Affiliate\Models\Affiliate::find($this->wallet->holder_id);
        $commission = $affiliate->commissions()
            ->where('id', $this->meta['commission_id'])
            ->first();

        $invoiceFrom = multi_tag_explode([',', '<br/>'], strip_tags(get_platform_address(), '<br>'));
        array_unshift($invoiceFrom, get_platform_title());
        unset($invoiceFrom['address_type']);

        if (! is_null($affiliate)) {
            $invoiceTo = [$affiliate->name, $affiliate->email, $affiliate->phone];
        }

        $dataForInvoice = [
            'invoice_from' => $invoiceFrom,
            'invoice_to' => $invoiceTo ?? [],
            'transaction' => $this,
            'commission' => $commission,
        ];

        $pdfGenerator = new PdfGenerator;
        $pdfTemplate = PdfTemplate::where('type', PdfTemplate::TYPE_AFFILIATE_WALLET_TRANSACTION)->where('is_default', true)->first();

        return $pdfGenerator->setGeneratedFileName(get_platform_title().' - '.$this->unique_id)
            ->generatePdfFromTemplate($dataForInvoice, $pdfTemplate, 'a4', $action);
    }
}
