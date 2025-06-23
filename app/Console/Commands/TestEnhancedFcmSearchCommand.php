<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Services\FCMService;

class TestEnhancedFcmSearchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:test-enhanced-search {--customer_id=} {--send-notification : Actually send a test notification}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the enhanced FCM token search functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Enhanced FCM Token Search...');
        $this->line('');

        $fcmService = new FCMService();
        $sendNotification = $this->option('send-notification');

        // Test specific customer if provided
        if ($this->option('customer_id')) {
            $customerId = $this->option('customer_id');
            $customer = Customer::find($customerId);

            if (!$customer) {
                $this->error("Customer with ID {$customerId} not found");
                return 1;
            }

            $this->testCustomerFcmSearch($fcmService, $customer, $sendNotification);
            return 0;
        }

        // Test all customers
        $customers = Customer::with('user')->get();

        if ($customers->isEmpty()) {
            $this->warn('No customers found in database');
            return 0;
        }

        $this->info("Found {$customers->count()} customers. Testing FCM token search for each:");
        $this->line('');

        foreach ($customers as $customer) {
            $this->testCustomerFcmSearch($fcmService, $customer, $sendNotification);
            $this->line('');
        }

        return 0;
    }

    private function testCustomerFcmSearch(FCMService $fcmService, Customer $customer, bool $sendNotification = false)
    {
        $this->info("Testing Customer ID: {$customer->id}");
        $this->info("  Direct User: " . ($customer->user ? "{$customer->user->name} (ID: {$customer->user->id})" : 'None'));
        $this->info("  Direct FCM Token: " . ($customer->user && $customer->user->fcm_token ? 'Present' : 'None'));

        // Test enhanced search
        $tokenInfo = $fcmService->findCustomerFcmToken($customer, $customer->id);

        if ($tokenInfo) {
            $this->info("  ✅ SUCCESS: FCM token found!");
            $this->info("    Search Method: {$tokenInfo['method']}");
            $this->info("    Target User: {$tokenInfo['user_name']} (ID: {$tokenInfo['user_id']})");
            $this->info("    Token: " . substr($tokenInfo['token'], 0, 30) . '...');

            if ($sendNotification) {
                $this->info("  📤 Sending test notification...");

                $result = $fcmService->sendNotification(
                    $tokenInfo['token'],
                    'Test Enhanced FCM Search',
                    "Test notification for customer {$customer->id} via enhanced search method: {$tokenInfo['method']}",
                    [
                        'test_type' => 'enhanced_search',
                        'customer_id' => $customer->id,
                        'search_method' => $tokenInfo['method'],
                        'target_user_id' => $tokenInfo['user_id'],
                    ]
                );

                if ($result['success']) {
                    $this->info("    ✅ Notification sent successfully!");
                } else {
                    $this->warn("    ⚠️ Notification failed: {$result['message']}");
                }
            }
        } else {
            $this->warn("  ❌ FAILED: No FCM token found for customer {$customer->id}");
        }
    }
}
