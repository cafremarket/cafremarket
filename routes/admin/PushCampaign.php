<?php

use App\Http\Controllers\Admin\PushCampaignController;
use Illuminate\Support\Facades\Route;

Route::post('push-campaign/{push_campaign}/send', [PushCampaignController::class, 'send'])
    ->name('push_campaign.send');

Route::resource('push-campaign', PushCampaignController::class)
    ->names('push_campaign')
    ->parameters(['push-campaign' => 'push_campaign']);
