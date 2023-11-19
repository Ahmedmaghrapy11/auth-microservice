<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\UserAuthenticated;
use Enqueue\LaravelQueue\Jobs\RabbitMQJob;

class PublishUserAuthenticatedEvent implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     */
    public function handle(UserAuthenticated $event)
    {
        $connectionFactory = new \Enqueue\AmqpExt\AmqpConnectionFactory(env('RABBITMQ_DSN'));
        $context = $connectionFactory->createContext();
        $message = $context->createMessage(json_encode(['user_id' => $event->userId]));

        $context->createProducer()->send($context->createQueue('user.authenticated'), $message);
    }
}
