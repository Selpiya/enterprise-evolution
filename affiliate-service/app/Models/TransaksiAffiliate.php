<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiAffiliate extends Model
{
    protected $fillable = [
        'transaction_id',
        'creator_id',
        'campaign_id',
        'nominal_transaksi',
        'nmv',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'nominal_transaksi' => 'integer',
        'nmv' => 'integer',
    ];
}