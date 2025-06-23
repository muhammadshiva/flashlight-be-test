<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\FCMService;
use Illuminate\Support\Facades\Log;

class CleanInvalidFcmTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:clean-invalid-tokens {--dry-run : Show what would be cleaned without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up invalid FCM tokens from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting FCM token validation...');

        $fcmService = new FCMService();
        $dryRun = $this->option('dry-run');

        // Get all users with FCM tokens
        $usersWithTokens = User::whereNotNull('fcm_token')->get();

        if ($usersWithTokens->isEmpty()) {
            $this->info('No users with FCM tokens found.');
            return 0;
        }

        $this->info("Found {$usersWithTokens->count()} users with FCM tokens.");
        $this->line('');

        $invalidTokenCount = 0;
        $validTokenCount = 0;

        // Test each token by sending a test notification
        foreach ($usersWithTokens as $user) {
            $this->info("Testing token for user: {$user->name} (ID: {$user->id})");

            // Send a silent test notification
            $result = $fcmService->sendNotification(
                $user->fcm_token,
                'Test Notification',
                'Testing token validity',
                ['test' => true, 'silent' => true]
            );

            if (!$result['success']) {
                $errorCode = $result['error_code'] ?? null;

                if ($errorCode === 'UNREGISTERED' || $errorCode === 'INVALID_ARGUMENT') {
                    $this->warn("  ❌ Invalid token (Error: {$errorCode})");
                    $invalidTokenCount++;

                    if (!$dryRun) {
                        $user->clearFcmToken();
                        $this->info("  🧹 Token cleared from database");

                        Log::info('Invalid FCM token cleared', [
                            'user_id' => $user->id,
                            'user_email' => $user->email,
                            'error_code' => $errorCode,
                            'token_prefix' => substr($user->fcm_token, 0, 20) . '...',
                        ]);
                    } else {
                        $this->info("  🔍 Would clear token (dry-run mode)");
                    }
                } else {
                    $this->warn("  ⚠️ Token error but not invalid: {$result['message']}");
                    $validTokenCount++;
                }
            } else {
                $this->info("  ✅ Token is valid");
                $validTokenCount++;
            }
        }

        $this->line('');
        $this->info('=== Summary ===');
        $this->info("Valid tokens: {$validTokenCount}");
        $this->info("Invalid tokens: {$invalidTokenCount}");

        if ($dryRun && $invalidTokenCount > 0) {
            $this->line('');
            $this->warn("Run without --dry-run to actually clean {$invalidTokenCount} invalid tokens.");
        } elseif (!$dryRun && $invalidTokenCount > 0) {
            $this->line('');
            $this->info("✅ Cleaned {$invalidTokenCount} invalid FCM tokens from database.");
        }

        return 0;
    }
}
