<?php

namespace App\Services;

use App\Models\TransaksiAffiliate;

class SimpanTransaksiAffiliateDariEvent
{
    public function handle(array $data): TransaksiAffiliate
    {
        return TransaksiAffiliate::updateOrCreate(
            [
                'transaction_id' => $data['transaction_id'],
            ],
            [
                'creator_id' => $data['creator_id'],
                'campaign_id' => $data['campaign_id'],
                'nominal_transaksi' => $data['nominal_transaksi'],
                'nmv' => $data['nmv'],
                'occurred_at' => $data['occurred_at'],
            ]
        );
    }
}