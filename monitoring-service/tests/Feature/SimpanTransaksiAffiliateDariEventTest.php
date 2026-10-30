<?php

namespace Tests\Feature;

use App\Models\TransaksiAffiliate;
use App\Services\SimpanTransaksiAffiliateDariEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpanTransaksiAffiliateDariEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_transaksi_affiliate_disimpan_ke_database(): void
    {
        $service = app(SimpanTransaksiAffiliateDariEvent::class);

        $service->handle([
            'transaction_id' => 'TRX-TEST001',
            'creator_id' => 'CRT-001',
            'campaign_id' => 'CMP-001',
            'nominal_transaksi' => 250000,
            'nmv' => 250000,
            'occurred_at' => now()->toIso8601String(),
        ]);

        $this->assertDatabaseHas('transaksi_affiliates', [
            'transaction_id' => 'TRX-TEST001',
            'creator_id' => 'CRT-001',
            'campaign_id' => 'CMP-001',
            'nominal_transaksi' => 250000,
            'nmv' => 250000,
        ]);
    }

    public function test_event_yang_sama_tidak_membuat_data_duplikat(): void
    {
        $service = app(SimpanTransaksiAffiliateDariEvent::class);

        $payload = [
            'transaction_id' => 'TRX-TEST001',
            'creator_id' => 'CRT-001',
            'campaign_id' => 'CMP-001',
            'nominal_transaksi' => 250000,
            'nmv' => 250000,
            'occurred_at' => now()->toIso8601String(),
        ];

        $service->handle($payload);
        $service->handle($payload);

        $this->assertSame(
            1,
            TransaksiAffiliate::where(
                'transaction_id',
                'TRX-TEST001'
            )->count()
        );
    }
}