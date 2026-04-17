<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Validations\MerchantImportRequest;
use App\Http\Requests\Validations\MerchantUploadRequest;
use App\Jobs\ProcessMerchantCsvBulkUpload as ProcessUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\MessageBag;
use Rap2hpoutre\FastExcel\FastExcel;

class MerchantUploadController extends Controller
{
    /**
     * Show upload form
     *
     * @return \Illuminate\View\View
     */
    public function showForm()
    {
        return view('admin.merchant._upload_form');
    }

    /**
     * Upload the csv file and generate the review table
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function upload(MerchantUploadRequest $request)
    {
        $path = $request->file('merchants')->getRealPath();
        $records = array_map('str_getcsv', file($path));

        // Validations check for csv_import_limit
        if ((count($records) - 1) > get_csv_import_limit()) {
            $err = (new MessageBag)->add('error', trans('validation.upload_rows', ['rows' => get_csv_import_limit()]));

            return back()->withErrors($err);
        }

        // Get field names from header column
        $fields = array_map('strtolower', $records[0]);

        // Remove the header column
        array_shift($records);

        // Check if Dynamic Commission enabled or not
        $dynamicCommission = is_incevio_package_loaded('dynamicCommission');

        $rows = [];
        foreach ($records as $record) {
            // \Log::info($fields);   \Log::info($record);
            if (count($fields) != count($record)) {
                $err = (new MessageBag)->add('error', trans('validation.csv_upload_invalid_data'));

                return back()->withErrors($err);
            }

            // Decode unwanted html entities
            $record = array_map('html_entity_decode', $record);

            // Set the field name as key
            $temp = array_combine($fields, $record);

            // Set Dynamic Commission fields
            if (! $dynamicCommission && isset($temp['commission_rate'])) {
                unset($temp['commission_rate']);
            }

            // Get the clean data
            $rows[] = clear_encoding_str($temp);
        }

        return view('admin.merchant.upload_review', compact('rows'));
    }

    /**
     * Perform import action
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function import(MerchantImportRequest $request)
    {
        if (config('app.demo') == true) {
            return redirect()->route('admin.vendor.merchant.index')
                ->with('warning', trans('messages.demo_restriction'));
        }

        if (config('queue.default') == 'sync') {
            ProcessUpload::dispatchSync(Auth::user(), $request->input('data'));

            if (Session::has('failed_rows')) {
                $failed_rows = Session::get('failed_rows');

                if (count($failed_rows) > 0 && ! empty($failed_rows[0])) {
                    return view('admin.merchant.import_failed', compact('failed_rows'));
                }
            }

            return redirect()->route('admin.vendor.merchant.index')
                ->with('success', trans('messages.imported', ['model' => trans('app.model.merchant')]));
        }

        ProcessUpload::dispatch(Auth::user(), $request->input('data'));

        return redirect()->route('admin.vendor.merchant.index')
            ->with('global_notice', trans('messages.csv_import_process_started'));
    }

    /**
     * Download the template file for Merchant csv upload.
     *
     * @return \Response response
     */
    public function downloadTemplate()
    {
        return response()->download(public_path('csv_templates/merchants.csv'));
    }

    /**
     * Download the failed rows as an Excel file.
     *
     * @return string|\Symfony\Component\HttpFoundation\BinaryFileResponse an Excel file that contains the failed rows
     */
    public function downloadFailedRows(Request $request)
    {
        foreach ($request->input('data') as $row) {
            $data[] = unserialize($row);
        }

        return (new FastExcel(collect($data)))->download('failed_rows.xlsx');
    }
}
