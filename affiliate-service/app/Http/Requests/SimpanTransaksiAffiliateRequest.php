<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SimpanTransaksiAffiliateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'creator_id' => ['required', 'string', 'max:100'],
            'campaign_id' => ['required', 'string', 'max:100'],
            'nominal_transaksi' => ['required', 'integer', 'min:0'],
            'nmv' => ['required', 'integer', 'min:0'],
        ];
    }
}