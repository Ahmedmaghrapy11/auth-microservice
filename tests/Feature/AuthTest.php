<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{

    use RefreshDatabase;

    /**
     * A basic feature test example.
     */

    public function testUserCanLogin()
    {
        // Create a user
        $user = User::factory()->create([
            'password' => Hash::make('Password11**'),
        ]);

        // Attempt to log in
        $response = $this->post('/api/login', [
            'email' => $user->email,
            'password' => 'Password11**',
        ]);

        $response->assertStatus(200);
        $this->assertArrayHasKey('token', $response->json());
    }

    public function testUserCanRegister()
    {
        // Simulate user registration
        $response = $this->post('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password11**',
            'password_confirmation' => 'Password11**'
        ]);

        $response->assertStatus(200);
        $this->assertArrayHasKey('token', $response->json());
    }

    public function testUserCanLogout()
    {
        // Simulate user login
        $user = User::factory()->create([
            'password' => Hash::make('Password11**'),
        ]);

        $token = JWTAuth::fromUser($user);

        // Simulate user logout
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/api/logout');

        $response->assertStatus(200);
        $this->assertEquals('Logged out successfully !', $response->json('message'));
    }

}
