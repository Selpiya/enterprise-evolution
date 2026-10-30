<?php

use App\Http\Controllers\Api\V1\TransaksiAffiliateController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post(
        '/transaksi-affiliate',
        [TransaksiAffiliateController::class, 'store']
    );

    Route::get(
        '/transaksi-affiliate/{transaction_id}',
        [TransaksiAffiliateController::class, 'show']
    );
});