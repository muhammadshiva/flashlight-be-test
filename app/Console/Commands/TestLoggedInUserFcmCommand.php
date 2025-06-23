<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\FCMService;

class TestLoggedInUserFcmCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:test-logged-in-user {--user_id=} {--send-notification : Actually send a test notification}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test FCM notifications for logged in users (cashier/admin) during payment processing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing FCM Notifications for Logged In Users (Cashier/Admin)...');
        $this->line('');

        $fcmService = new FCMService();
        $sendNotification = $this->option('send-notification');

        // Test specific user if provided
        if ($this->option('user_id')) {
            $userId = $this->option('user_id');
            $user = User::find($userId);

            if (!$user) {
                $this->error("User with ID {$userId} not found");
                return 1;
            }

            $this->testUserFcmNotification($fcmService, $user, $sendNotification);
            return 0;
        }

        // Test all admin/cashier/staff users
        $staffUsers = User::whereIn('type', ['admin', 'cashier', 'staff', 'owner'])
            ->orderBy('id')
            ->get();

        if ($staffUsers->isEmpty()) {
            $this->warn('No admin/cashier/staff users found in database');
            return 0;
        }

        $this->info("Found {$staffUsers->count()} admin/cashier/staff users. Testing FCM notifications for each:");
        $this->line('');

        foreach ($staffUsers as $user) {
            $this->testUserFcmNotification($fcmService, $user, $sendNotification);
            $this->line('');
        }

        return 0;
    }

    private function testUserFcmNotification(FCMService $fcmService, User $user, bool $sendNotification = false)
    {
        $this->info("Testing User: {$user->name} (ID: {$user->id})");
        $this->info("  User Type: {$user->type}");
        $this->info("  Email: {$user->email}");
        $this->info("  FCM Token: " . ($user->hasFcmToken() ? 'Present' : 'None'));

        if ($user->hasFcmToken()) {
            $this->info("  ✅ User has FCM token!");
            $this->info("    Token: " . substr($user->fcm_token, 0, 30) . '...');

            if ($sendNotification) {
                $this->info("  📤 Sending test payment notification...");

                // Simulate payment completion notification for cashier/admin
                $result = $fcmService->sendNotification(
                    $user->fcm_token,
                    'Payment Processed Successfully',
                    "Payment completed for Customer John Doe. Cash payment of IDR 75,000 has been processed successfully.",
                    [
                        'test_type' => 'logged_in_user_payment',
                        'user_id' => $user->id,
                        'user_type' => $user->type,
                        'type' => 'cash_payment_completed',
                        'amount_paid' => '75000',
                        'customer_name' => 'John Doe',
                        'is_print_receipt' => true,
                    ]
                );

                if ($result['success']) {
                    $this->info("    ✅ Payment notification sent successfully!");
                } else {
                    $this->warn("    ⚠️ Payment notification failed: {$result['message']}");
                    if (isset($result['error_code']) && $result['error_code'] === 'UNREGISTERED') {
                        $this->warn("    🧹 Token is invalid and should be cleared");
                    }
                }
            }
        } else {
            $this->warn("  ❌ User does not have FCM token");
            $this->info("    Note: This user won't receive payment notifications until they login to mobile app and set FCM token");
        }
    }
}
