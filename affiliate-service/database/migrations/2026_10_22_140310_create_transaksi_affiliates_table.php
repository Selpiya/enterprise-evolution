<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaksi_affiliates', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->string('creator_id');
            $table->string('campaign_id');
            $table->unsignedBigInteger('nominal_transaksi');
            $table->unsignedBigInteger('nmv');
            $table->timestamp('occurred_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_affiliates');
    }
};
