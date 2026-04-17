<?php

namespace Incevio\Package\Wallet\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\MessageBag;
use Incevio\Package\Wallet\Exceptions\WalletOwnerInvalid;
use Incevio\Package\Wallet\Http\Requests\WalletBulkUploadRequest;
use Incevio\Package\Wallet\Models\Transaction;
use Incevio\Package\Wallet\Models\Wallet;
use Incevio\Package\Wallet\Traits\HasTransaction;
use Rap2hpoutre\FastExcel\FastExcel;

class WalletBulkDepositController extends Controller
{
    use HasTransaction;

    private $failed_list = [];

    // Fields or columns that must be present in each row.
    private $required_fields = ['coupon_code', 'email', 'amount', 'user_type', 'currency_code'];

    /**
     * Show Bulk upload index
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Gate::authorize('payout', Wallet::class);

        $all_deposits = Transaction::deposits()->orderBy('created_at', 'desc')->get();

        $bulkupload_deposits = $all_deposits->filter(function ($deposit) {
            return isset($deposit->meta) && isset($deposit->meta['coupon_code']);
        });

        return view('wallet::admin.bulkupload_index', compact('bulkupload_deposits'));
    }

    /**
     * Show upload form
     *
     * @return \Illuminate\Http\Response
     */
    public function showForm()
    {
        return view('wallet::admin._bulk_transaction_form');
    }

    /**
     * Upload the csv file and generate the review table
     *
     * @return \Illuminate\Http\Response
     */
    public function upload(WalletBulkUploadRequest $request)
    {
        $path = $request->file('walletbulk')->getRealPath();
        $records = array_map('str_getcsv', file($path));

        // Validations check for csv_import_limit
        if (count($records) > get_csv_import_limit()) {
            $err = (new MessageBag)->add('error', trans('validation.upload_rows', ['rows' => get_csv_import_limit()]));

            return back()->withErrors($err);
        }

        // Get field names from header column
        $fields = array_map('strtolower', $records[0]);

        // Check if any column headers has been changed or missing.
        $missing_fields = array_diff($this->required_fields, $fields);
        if (! empty($missing_fields)) {
            $err = (new MessageBag)->add('error', trans('validation.csv_upload_invalid_data'));

            return back()->withErrors($err);
        }

        // Remove the header column
        array_shift($records);

        $rows = [];
        foreach ($records as $record) {
            if (count($fields) != count($record)) {
                $err = (new MessageBag)->add('error', trans('validation.csv_upload_invalid_data'));

                return back()->withErrors($err);
            }

            // Decode unwanted html entities
            $record = array_map('html_entity_decode', $record);

            $search = ['&#39;'];
            $replace = [' \' '];
            $record = str_replace($search, $replace, $record);

            // Set the field name as key
            $record = array_combine($fields, $record);

            // Get the clean data
            $rows[] = clear_encoding_str($record);
        }

        return view('wallet::admin.upload_review', compact('rows'));
    }

    /**
     * Perform import action (perform deposit in each wallet)
     *
     * @return \Illuminate\Http\Response
     */
    public function import(WalletBulkUploadRequest $request)
    {
        // Reset the Failed list
        $this->failed_list = [];

        $records = $request->input('data');

        foreach ($records as $row) {
            $data = unserialize($row);

            if ($this->dataHasMissingField($data)) {
                $this->pushIntoFailed($data, trans('help.missing_required_data'));

                continue;
            }

            // perform transfer below
            try {
                $wallet = $this->getWallet($data['user_type'], $data['email']); // get wallet for each customer

                // When the wallet not exist
                if (! $wallet) {
                    throw new WalletOwnerInvalid(trans('packages.wallet.wallet_email_not_found', ['email' => $data['email']]));
                }

                $meta = [];
                $meta = array_merge([
                    'type' => Transaction::TYPE_DEPOSIT,
                    'coupon_code' => $data['coupon_code'],
                    'email' => $data['email'],
                    'description' => trans('packages.wallet.bulk_deposit_description'),
                ], $meta);

                $wallet->deposit(floatval($data['amount']), $meta);
            } catch (\Exception $error) {
                $this->pushIntoFailed($data, $error->getMessage());

                \Log::error($error);

                continue;
            }
        }

        $request->session()->flash('success', trans('messages.imported', ['model' => trans('packages.wallet.transactions')]));

        $failed_rows = $this->getFailedList();

        if (empty($failed_rows)) {
            return redirect()->route('admin.wallet.bulkupload.index');
        }

        return view('wallet::admin.import_failed', compact('failed_rows'));
    }

    /**
     * Check if the data has all the required fields
     *
     * @param mixed the row of containing data of all the fields uploaded in csv
     * @return bool True if theres a field missing (null value or empty string)
     */
    public function dataHasMissingField($data)
    {
        if (! isset($data) || ! is_array($data)) {
            return true; // Data is not set or not an array
        }

        foreach ($this->required_fields as $field) {
            if (! isset($data[$field]) || empty($data[$field])) {
                return true; // When Field is missing or empty
            }
        }

        // Remove dots and check remaining characters
        if (! ctype_digit(str_replace('.', '', $data['amount']))) {
            return true; // if amount contains non numeric characters
        }

        return false;
    }

    /**
     * download Template
     *
     * @return response response
     */
    public function downloadTemplate()
    {
        $pathToFile = public_path('csv_templates/walletbulkupload.csv');

        return response()->download($pathToFile);
    }

    /**
     * [downloadFailedRows]
     *
     * @param  Excel  $excel
     */
    public function downloadFailedRows(Request $request)
    {
        foreach ($request->input('data') as $row) {
            $data[] = unserialize($row);
        }

        return (new FastExcel(collect($data)))->download('failed_rows.xlsx');
    }

    /**
     * Push New value Into Failed List
     *
     * @param  string  $reason
     * @return void
     */
    private function pushIntoFailed(array $data, $reason = null)
    {
        $row = [
            'data' => $data,
            'reason' => $reason,
        ];

        array_push($this->failed_list, $row);
    }

    /**
     * Return the failed list
     *
     * @return array
     */
    private function getFailedList()
    {
        return $this->failed_list;
    }
}
