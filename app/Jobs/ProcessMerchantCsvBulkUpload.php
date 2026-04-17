<?php

namespace App\Jobs;

use App\Events\Shop\ShopCreated;
use App\Events\User\UserCreated;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\Inventory\ProcessedCsvImport;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Rap2hpoutre\FastExcel\FastExcel;

class ProcessMerchantCsvBulkUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 5;

    /**
     * The number of seconds the job can run before timing out.
     */
    public $timeout = 1200;

    public $user;

    private $csv_data;

    private $failed_list = [];

    private $success_counter;

    private $failed_file_path = '';

    /**
     * Delete the job if its models no longer exist.
     *
     * @var bool
     */
    public $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, $csv_data = [])
    {
        $this->user = $user;
        $this->csv_data = $csv_data;
        $this->success_counter = 0;
        $this->failed_list = [];

        ini_set('max_execution_time', 0); // Set unlimited time
        ini_set('max_input_vars', 100000); // Set a larger value
    }

    /**
     * Execute the job.
     *
     * @return array $failed_rows
     */
    public function handle()
    {
        Session::forget('failed_rows'); // Clear failed_rows cache

        foreach ($this->csv_data as $row) {
            $data = unserialize($row);

            // Invalid data
            if (! is_array($data)) {
                continue;
            }

            // Ignore if required info is not given
            if (! verifyRequiredDataForBulkUpload($data, 'merchant_upload')) {
                $this->pushIntoFailed($data, trans('help.missing_required_data'));

                continue;
            }

            // If the slug is not given the make it
            if (! isset($data['slug'])) {
                $data['slug'] = convertToSlugString($data['shop_name']);
            }

            // Prepare address fields
            $data['address_title'] = $data['name'];
            $data['country_id'] = $data['country']; // DONT MOVE:: This line must be state_id
            $data['state_id'] = $data['province_state'];

            // Set default password
            if (! isset($data['password'])) {
                $data['password'] = 123456;
            }

            // Set custom member since
            if (isset($data['member_since']) && $data['member_since'] != '') {
                $data['created_at'] = $data['member_since'];
            }

            // Ignore if the slug is exist in the database
            $item = Shop::select('slug')->where('slug', $data['slug'])->first();
            if ($item) {
                $this->pushIntoFailed($data, trans('help.slug_already_exist'));

                continue;
            }

            // Start transaction!
            DB::beginTransaction();

            try {
                $merchant = User::create($data);

                $merchant->addresses()->create($data);

                // Dispatching Shop create job
                if (config('queue.default') == 'sync') {
                    CreateShopForMerchant::dispatchSync($merchant, $data);
                } else {
                    CreateShopForMerchant::dispatch($merchant, $data);
                }

                // Create subscription when enabled
                if (is_subscription_enabled()) {
                    SubscribeShopToNewPlan::dispatch($merchant, $data['current_billing_plan']);
                }

                // Everything is fine. Now commit the transaction
                DB::commit();

                $this->success_counter++; // Increase the counter for successful import

                // Trigger user created event
                event(new UserCreated($merchant, $data['name'], $data['password']));

                // Trigger shop created event
                event(new ShopCreated($merchant->owns));

                // Save avatar image from url
                if (isset($data['avatar']) && filter_var($data['avatar'], FILTER_VALIDATE_URL)) {
                    $merchant->saveImageFromUrl($data['avatar'], 'avatar');
                }
            } catch (\Exception $e) {
                DB::rollback(); // Rollback the transaction and log the error

                $this->pushIntoFailed($data, $e->getMessage());
            }
        }

        $failed_rows = $this->getFailedList();

        // When the job processing on current request cycle
        if (config('queue.default') != 'sync' && ! empty($failed_rows)) {
            $this->failed_file_path = $this->createAttachmentWithFailedRows();
            $this->user->notify(new ProcessedCsvImport($failed_rows, $this->success_counter, $this->failed_file_path));
        } else {
            Session::push('failed_rows', $failed_rows);
        }
    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    // public function failed(Exception $exception)
    // {
    //     // Send user notification of failure, etc...
    // }

    /**
     * Push New value Into Failed List
     *
     * @param  string  $reason
     * @return void
     */
    private function pushIntoFailed(array $data, $reason = null)
    {
        array_push($this->failed_list, [
            'data' => $data,
            'reason' => $reason,
        ]);
    }

    /**
     * create attachment with failed data
     *
     * @param  Excel  $excel
     */
    public function createAttachmentWithFailedRows()
    {
        $data = [];
        foreach ($this->getFailedList() as $row) {
            $data[] = $row;
        }

        $path = storage_path('failed_rows.xlsx');

        return (new FastExcel(collect($data)))->export($path);
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
