<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\RolePermissionSeeder;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed the roles and permissions
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_without_fcm_token_and_device_id()
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'type' => 'customer',
        ]);

        $user->customer()->create(['is_active' => true]);

        // Test login without FCM token and device ID
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'fcm_token_updated' => false,
                    'device_token_stored' => false,
                ]
            ])
            ->assertJsonMissing(['device_id']);
    }

    public function test_login_with_fcm_token_and_device_id()
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'type' => 'customer',
        ]);

        $user->customer()->create(['is_active' => true]);

        // Test login with FCM token and device ID
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'fcm_token' => 'test_fcm_token_that_is_longer_than_fifty_characters_to_pass_validation',
            'device_id' => 'test_device_id',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'fcm_token_updated' => true,
                    'device_token_stored' => true,
                    'device_id' => 'test_device_id',
                ]
            ]);
    }

    public function test_login_with_only_fcm_token()
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'type' => 'customer',
        ]);

        $user->customer()->create(['is_active' => true]);

        // Test login with only FCM token
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'fcm_token' => 'test_fcm_token_that_is_longer_than_fifty_characters_to_pass_validation',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'fcm_token_updated' => true,
                    'device_token_stored' => false,
                ]
            ])
            ->assertJsonMissing(['device_id']);
    }

    public function test_login_with_only_device_id()
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'type' => 'customer',
        ]);

        $user->customer()->create(['is_active' => true]);

        // Test login with only device ID
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_id' => 'test_device_id',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'fcm_token_updated' => false,
                    'device_token_stored' => false,
                    'device_id' => 'test_device_id',
                ]
            ]);
    }
}
