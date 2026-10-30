<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\TransaksiAffiliateTercatat;
use App\Http\Controllers\Controller;
use App\Http\Requests\SimpanTransaksiAffiliateRequest;
use App\Http\Resources\TransaksiAffiliateResource;
use App\Models\TransaksiAffiliate;
use Illuminate\Support\Str;

class TransaksiAffiliateController extends Controller
{
    public function store(SimpanTransaksiAffiliateRequest $request)
    {
        $transaksi = TransaksiAffiliate::create([
            'transaction_id' => 'TRX-' . strtoupper(Str::random(8)),
            'creator_id' => $request->string('creator_id'),
            'campaign_id' => $request->string('campaign_id'),
            'nominal_transaksi' => $request->integer('nominal_transaksi'),
            'nmv' => $request->integer('nmv'),
            'occurred_at' => now(),
        ]);
        
        TransaksiAffiliateTercatat::dispatch($transaksi);

        return (new TransaksiAffiliateResource($transaksi))
            ->response()
            ->setStatusCode(201);
    }

    public function show(string $transaction_id)
    {
        $transaksi = TransaksiAffiliate::where(
            'transaction_id',
            $transaction_id
        )->firstOrFail();

        return new TransaksiAffiliateResource($transaksi);
    }
}