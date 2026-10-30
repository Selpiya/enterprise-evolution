<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransaksiAffiliateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'transaction_id' => $this->transaction_id,
            'creator_id' => $this->creator_id,
            'campaign_id' => $this->campaign_id,
            'nominal_transaksi' => $this->nominal_transaksi,
            'nmv' => $this->nmv,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
        ];
    }
}