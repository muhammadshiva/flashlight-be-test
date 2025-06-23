<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FCMService;

class TestFCMCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:test {--token=} {--title=Test FCM v1 API} {--body=This is a test notification using Firebase FCM v1 API from Laravel} {--user_id=} {--payment-test : Test payment notification simulation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test FCM notification functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Testing FCM v1 API notification...');

            // Option 1: Test using FCM Service directly with token
            if ($this->option('token')) {
                return $this->testWithToken();
            }

            // Option 2: Test payment notification simulation
            if ($this->option('payment-test')) {
                return $this->testPaymentNotification();
            }

            // Option 3: Test using Laravel Notification system with user
            if ($this->option('user_id')) {
                return $this->testWithUser();
            }

            // Option 4: Test FCM configuration and connection
            return $this->testConfiguration();
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Test FCM with direct token
     */
    private function testWithToken(): int
    {
        $fcmService = new FCMService();
        $token = $this->option('token');
        $title = $this->option('title');
        $body = $this->option('body');

        $this->info("Testing direct FCM call with v1 API...");
        $this->info("Token: " . substr($token, 0, 30) . "...");
        $this->info("Title: {$title}");
        $this->info("Body: {$body}");
        $this->line('');

        $result = $fcmService->sendNotification($token, $title, $body, [
            'test' => true,
            'timestamp' => now()->toISOString(),
            'command' => 'fcm:test',
            'method' => 'direct_token'
        ]);

        if ($result['success'] ?? false) {
            $this->info('✅ FCM notification sent successfully via direct token!');
            if (isset($result['data'])) {
                $this->info('Response: ' . json_encode($result['data'], JSON_PRETTY_PRINT));
            }
            return 0;
        } else {
            $this->error('❌ Failed to send FCM notification via direct token');
            $errorMessage = $result['error'] ?? $result['message'] ?? 'Unknown error';
            if (is_array($errorMessage)) {
                $errorMessage = json_encode($errorMessage, JSON_PRETTY_PRINT);
            }
            $this->error('Error: ' . $errorMessage);

            if (isset($result['full_response'])) {
                $this->error('Full Response: ' . json_encode($result['full_response'], JSON_PRETTY_PRINT));
            }
            return 1;
        }
    }

    /**
     * Test FCM with Laravel Notification system
     */
    private function testWithUser(): int
    {
        $userId = $this->option('user_id');
        $user = \App\Models\User::find($userId);

        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return 1;
        }

        if (!$user->hasFcmToken()) {
            $this->error("User {$user->name} doesn't have an FCM token");
            return 1;
        }

        $title = $this->option('title');
        $body = $this->option('body');

        $this->info("Testing Laravel Notification system...");
        $this->info("User: {$user->name} ({$user->email})");
        $this->info("FCM Token: " . substr($user->fcm_token, 0, 30) . "...");
        $this->info("Title: {$title}");
        $this->info("Body: {$body}");
        $this->line('');

        try {
            $notification = new \App\Notifications\FcmNotification($title, $body, [
                'test' => true,
                'timestamp' => now()->toISOString(),
                'command' => 'fcm:test',
                'method' => 'laravel_notification',
                'user_id' => $user->id,
            ]);

            $user->notify($notification);

            $this->info('✅ FCM notification sent successfully via Laravel Notification system!');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to send FCM notification via Laravel Notification system');
            $this->error('Error: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Test FCM simulating payment notification
     */
    private function testPaymentNotification(): int
    {
        $userId = $this->option('user_id');
        if (!$userId) {
            $this->error('--user_id is required for payment notification test');
            return 1;
        }

        $user = \App\Models\User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return 1;
        }

        if (!$user->hasFcmToken()) {
            $this->error("User {$user->name} doesn't have an FCM token");
            return 1;
        }

        $this->info("Testing Payment Notification simulation...");
        $this->info("User: {$user->name} ({$user->email})");
        $this->info("FCM Token: " . substr($user->fcm_token, 0, 30) . "...");
        $this->line('');

        try {
            // Simulate cash payment notification like in PaymentResource
            \Illuminate\Support\Facades\Log::info('Test Payment: Attempting FCM notification', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'fcm_token_exists' => $user->hasFcmToken(),
                'fcm_token_length' => strlen($user->fcm_token),
            ]);

            $notification = new \App\Notifications\FcmNotification(
                'Test Payment Completed Successfully',
                "Thank you {$user->name}! Your test payment has been processed successfully. Amount paid: IDR 50,000",
                [
                    'type' => 'test_payment_completed',
                    'is_print_receipt' => true,
                    'wash_transaction_id' => '999',
                    'payment_id' => '999',
                    'amount_paid' => '50000',
                    'change_amount' => '0',
                ]
            );

            $user->notify($notification);

            \Illuminate\Support\Facades\Log::info('Test Payment: FCM notification sent successfully', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);

            $this->info('✅ Payment notification test sent successfully!');
            return 0;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Test Payment: FCM notification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id,
            ]);

            $this->error('❌ Failed to send payment notification test');
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Test FCM configuration and connection
     */
    private function testConfiguration(): int
    {
        $fcmService = new FCMService();

        $this->info("Testing FCM v1 API configuration...");
        $this->line('');

        $result = $fcmService->testConnection();

        if ($result['success'] ?? false) {
            $this->info('✅ FCM configuration test successful!');
            if (isset($result['data'])) {
                $this->info('Response: ' . json_encode($result['data'], JSON_PRETTY_PRINT));
            }
            return 0;
        } else {
            $this->error('❌ FCM configuration test failed');
            $errorMessage = $result['error'] ?? $result['message'] ?? 'Unknown error';
            if (is_array($errorMessage)) {
                $errorMessage = json_encode($errorMessage, JSON_PRETTY_PRINT);
            }
            $this->error('Error: ' . $errorMessage);

            if (isset($result['full_response'])) {
                $this->error('Full Response: ' . json_encode($result['full_response'], JSON_PRETTY_PRINT));
            }
            return 1;
        }
    }
}
