<?php

namespace App\Services;

use Bunny\Client;

class RabbitMQService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'host' => config('rabbitmq.host'),
            'port' => config('rabbitmq.port'),
            'vhost' => config('rabbitmq.vhost'),
            'user' => config('rabbitmq.login'),
            'password' => config('rabbitmq.password'),
        ]);
    }

    public function connect()
    {
        $this->client->connect();
    }

    public function channel()
    {
        return $this->client->channel();
    }

    public function disconnect()
    {
        $this->client->disconnect();
    }
}
