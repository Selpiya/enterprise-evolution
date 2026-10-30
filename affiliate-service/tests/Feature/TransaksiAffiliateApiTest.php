<?php

namespace Tests\Feature;

use App\Models\TransaksiAffiliate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransaksiAffiliateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaksi_affiliate_dapat_dicatat(): void
    {
        $response = $this->postJson('/api/v1/transaksi-affiliate', [
            'creator_id' => 'CRT-001',
            'campaign_id' => 'CMP-001',
            'nominal_transaksi' => 250000,
            'nmv' => 250000,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.creator_id', 'CRT-001')
            ->assertJsonPath('data.campaign_id', 'CMP-001')
            ->assertJsonPath('data.nominal_transaksi', 250000)
            ->assertJsonPath('data.nmv', 250000);

        $this->assertDatabaseHas('transaksi_affiliates', [
            'creator_id' => 'CRT-001',
            'campaign_id' => 'CMP-001',
            'nominal_transaksi' => 250000,
            'nmv' => 250000,
        ]);
    }

    public function test_detail_transaksi_affiliate_dapat_diambil(): void
    {
        TransaksiAffiliate::create([
            'transaction_id' => 'TRX-TEST001',
            'creator_id' => 'CRT-001',
            'campaign_id' => 'CMP-001',
            'nominal_transaksi' => 250000,
            'nmv' => 250000,
            'occurred_at' => now(),
        ]);

        $this->getJson('/api/v1/transaksi-affiliate/TRX-TEST001')
            ->assertOk()
            ->assertJsonPath('data.transaction_id', 'TRX-TEST001')
            ->assertJsonPath('data.creator_id', 'CRT-001')
            ->assertJsonPath('data.campaign_id', 'CMP-001');
    }

    public function test_transaksi_yang_tidak_ditemukan_menghasilkan_problem_details(): void
    {
        $this->getJson('/api/v1/transaksi-affiliate/TRX-TIDAKADA')
            ->assertStatus(404)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJson([
                'type' => 'https://example.com/problems/not-found',
                'title' => 'Not Found',
                'status' => 404,
                'detail' => 'Transaksi affiliate tidak ditemukan.',
            ]);
    }

    public function test_data_tidak_valid_menghasilkan_validation_problem_details(): void
    {
        $this->postJson('/api/v1/transaksi-affiliate', [
            'campaign_id' => 'CMP-001',
            'nominal_transaksi' => 250000,
            'nmv' => 250000,
        ])
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'https://example.com/problems/validation-error')
            ->assertJsonPath('title', 'Validation Error')
            ->assertJsonPath('status', 422)
            ->assertJsonPath('detail', 'Data transaksi yang dikirim tidak valid.')
            ->assertJsonValidationErrors(['creator_id']);
    }
}