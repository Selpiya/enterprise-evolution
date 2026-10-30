<?php

namespace App\Messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitPublisher
{
    public function publish(array $payload): void
    {
        $connection = new AMQPStreamConnection(
            config('rabbitmq.host'),
            config('rabbitmq.port'),
            config('rabbitmq.user'),
            config('rabbitmq.password'),
            config('rabbitmq.vhost'),
        );

        $channel = $connection->channel();

        $exchange = config('rabbitmq.exchange');
        $queue = config('rabbitmq.queue');
        $routingKey = config('rabbitmq.routing_key');

        $channel->exchange_declare(
            $exchange,
            config('rabbitmq.exchange_type'),
            false,
            true,
            false
        );

        $channel->queue_declare(
            $queue,
            false,
            true,
            false,
            false
        );

        $channel->queue_bind(
            $queue,
            $exchange,
            $routingKey
        );

        $message = new AMQPMessage(
            json_encode($payload, JSON_THROW_ON_ERROR),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'type' => 'TransaksiAffiliateTercatat',
            ]
        );

        $channel->basic_publish(
            $message,
            $exchange,
            $routingKey
        );

        $channel->close();
        $connection->close();
    }
}