<?php

return [
    'host' => env('RABBITMQ_HOST', 'rabbitmq'),
    'port' => (int) env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'affiliate'),
    'password' => env('RABBITMQ_PASSWORD', 'pw123'),
    'vhost' => env('RABBITMQ_VHOST', '/'),

    'exchange' => 'affiliate.events',
    'exchange_type' => 'topic',

    'queue' => env(
        'RABBITMQ_QUEUE',
        'monitoring.transaksi-affiliate'
    ),

    'routing_key' => 'affiliate.transaksi.tercatat',
];