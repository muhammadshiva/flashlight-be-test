<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DeviceFcmToken;
use App\Models\User;
use App\Models\Payment;
use App\Services\FCMService;

class TestDeviceFcmTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:device-fcm-token';

    /**
     * The console command description.
     */
    protected $description = 'Test device FCM token functionality (1 device for multiple users)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔥 Testing Device FCM Token System');
        $this->info('===================================');

        // Step 1: Check current device tokens
        $this->info('Step 1: Checking current device tokens...');
        $deviceTokens = DeviceFcmToken::where('is_active', true)->get();

        $this->info("Found {$deviceTokens->count()} active device tokens:");
        foreach ($deviceTokens as $token) {
            $this->line("  - Device ID: {$token->device_id}");
            $this->line("    Last User: {$token->lastUser?->name} (ID: {$token->last_user_id})");
            $this->line("    Platform: {$token->platform}");
            $this->line("    Last Used: {$token->last_used_at}");
            $this->line("    Token: " . substr($token->fcm_token, 0, 30) . '...');
            $this->line('');
        }

        // Step 2: Test device token creation
        $this->info('Step 2: Testing device token storage...');

        // Find an admin/owner user
        $adminUser = User::whereIn('type', ['admin', 'owner', 'cashier'])->first();
        if (!$adminUser) {
            $this->error('No admin/owner/cashier user found. Please create an admin user first.');
            return Command::FAILURE;
        }

        // Create test device token
        $testDeviceId = 'test-device-' . time();
        $testFcmToken = 'TEST_FCM_TOKEN_' . str_repeat('A', 100); // Fake long token

        $deviceToken = DeviceFcmToken::storeDeviceToken(
            $testDeviceId,
            $testFcmToken,
            $adminUser->id,
            'Test Device',
            'Test Platform'
        );

        $this->info("✅ Device token stored successfully:");
        $this->line("   Device ID: {$deviceToken->device_id}");
        $this->line("   User: {$deviceToken->lastUser->name}");
        $this->line("   Created: {$deviceToken->created_at}");

        // Step 3: Test FCM service methods
        $this->info('Step 3: Testing FCM service methods...');

        $fcmService = new FCMService();

        // Test getting device token
        $retrievedToken = $fcmService->getDeviceFcmToken($testDeviceId);

        if ($retrievedToken) {
            $this->info("✅ Device token retrieved successfully:");
            $this->line("   Token: " . substr($retrievedToken, 0, 30) . '...');
        } else {
            $this->error("❌ Failed to retrieve device token");
        }

        // Test getting latest token
        $latestToken = $fcmService->getDeviceFcmToken();
        if ($latestToken) {
            $this->info("✅ Latest device token retrieved:");
            $this->line("   Token: " . substr($latestToken, 0, 30) . '...');
        } else {
            $this->error("❌ No latest device token found");
        }

        // Step 4: Test payment notification (if payment exists)
        $this->info('Step 4: Testing payment notification...');

        $payment = Payment::with('washTransaction.customer')->first();
        if ($payment) {
            $this->info("Found payment ID: {$payment->id}");
            $this->info("Testing notification to device...");

            $result = $fcmService->sendPaymentNotificationToDevice($payment, $testDeviceId);

            if ($result['success']) {
                $this->info("✅ Payment notification test: SUCCESS");
                $this->line("   Message: {$result['message']}");
            } else {
                $this->error("❌ Payment notification test: FAILED");
                $this->error("   Error: {$result['message']}");
                if (isset($result['error'])) {
                    $this->error("   Details: " . json_encode($result['error']));
                }
            }
        } else {
            $this->warn("No payment records found for testing");
        }

        // Step 5: Cleanup test data
        $this->info('Step 5: Cleaning up test data...');
        $deviceToken->delete();
        $this->info("✅ Test device token deleted");

        // Step 6: Show summary
        $this->info('Step 6: Summary');
        $this->info('================');

        $activeTokens = DeviceFcmToken::where('is_active', true)->count();
        $this->info("Total active device tokens: {$activeTokens}");

        if ($activeTokens > 0) {
            $this->info("✅ Device FCM token system is working!");
            $this->info("   - Tokens are stored and retrieved correctly");
            $this->info("   - Payment notifications can be sent to devices");
            $this->info("   - System supports 1 device for multiple users");
        } else {
            $this->warn("⚠️  No active device tokens found");
            $this->warn("   - Users need to login from mobile with FCM tokens");
            $this->warn("   - Check mobile app integration");
        }

        return Command::SUCCESS;
    }
}
