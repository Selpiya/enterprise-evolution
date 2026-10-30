<?php

namespace App\Listeners;

use App\Events\TransaksiAffiliateTercatat;
use App\Messaging\RabbitPublisher;

class TerbitkanTransaksiAffiliateTercatat
{
    public function __construct(
        private RabbitPublisher $publisher
    ) {
    }

    public function handle(TransaksiAffiliateTercatat $event): void
    {
        $this->publisher->publish(
            $event->payload()
        );
    }
}