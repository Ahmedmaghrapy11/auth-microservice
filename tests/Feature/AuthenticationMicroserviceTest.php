<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Events\UserAuthenticated;
use Illuminate\Support\Facades\Event;

class AuthenticationMicroserviceTest extends TestCase
{
    /** @test */
    public function it_sends_user_authenticated_message_to_rabbitmq()
    {
        Event::fake();

        // Your authentication logic that triggers the event
        // For example, simulate a user login
        event(new UserAuthenticated(1));

        // Assert that the event was dispatched
        Event::assertDispatched(UserAuthenticated::class);
    }
}
