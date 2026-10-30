<?php

namespace App\Console\Commands;

use App\Services\SimpanTransaksiAffiliateDariEvent;
use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class KonsumsiTransaksiAffiliate extends Command
{
    protected $signature = 'monitoring:konsumsi-transaksi-affiliate';

    protected $description = 'Mengonsumsi event transaksi affiliate dari RabbitMQ';

    public function handle(SimpanTransaksiAffiliateDariEvent $penyimpan): int
    {
        $connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST'),
            (int) env('RABBITMQ_PORT'),
            env('RABBITMQ_USER'),
            env('RABBITMQ_PASSWORD'),
            env('RABBITMQ_VHOST', '/'),
        );

        $channel = $connection->channel();

        $queue = env(
            'RABBITMQ_QUEUE',
            'monitoring.transaksi-affiliate'
        );

        $channel->queue_declare(
            $queue,
            false,
            true,
            false,
            false
        );

        $this->info(
            "Menunggu pesan di queue {$queue}. Tekan Ctrl+C untuk berhenti."
        );

        $callback = function (AMQPMessage $message) use ($penyimpan): void {
            $data = json_decode(
                $message->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

        $penyimpan->handle($data);

            $this->line(
                'Diterima: ' . json_encode($data)
            );

            $message->ack();
        };

        $channel->basic_consume(
            $queue,
            '',
            false,
            false,
            false,
            false,
            $callback
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

        return self::SUCCESS;
    }
}