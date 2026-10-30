<?php

namespace App\Events;

use App\Models\TransaksiAffiliate;
use Illuminate\Foundation\Events\Dispatchable;

class TransaksiAffiliateTercatat
{
    use Dispatchable;

    public function __construct(
        public TransaksiAffiliate $transaksi
    ) {
    }

    public function payload(): array
    {
        return [
            'transaction_id' => $this->transaksi->transaction_id,
            'creator_id' => $this->transaksi->creator_id,
            'campaign_id' => $this->transaksi->campaign_id,
            'nominal_transaksi' => $this->transaksi->nominal_transaksi,
            'nmv' => $this->transaksi->nmv,
            'occurred_at' => $this->transaksi->occurred_at?->toIso8601String(),
        ];
    }
}