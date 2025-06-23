<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceFcmToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'fcm_token',
        'last_user_id',
        'device_name',
        'platform',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who last used this device
     */
    public function lastUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_user_id');
    }

    /**
     * Get active FCM token for a device
     */
    public static function getActiveTokenForDevice(string $deviceId): ?string
    {
        $deviceToken = self::where('device_id', $deviceId)
            ->where('is_active', true)
            ->first();

        return $deviceToken?->fcm_token;
    }

    /**
     * Get the most recent active FCM token
     */
    public static function getLatestActiveToken(): ?string
    {
        $deviceToken = self::where('is_active', true)
            ->orderBy('last_used_at', 'desc')
            ->first();

        return $deviceToken?->fcm_token;
    }

    /**
     * Store or update FCM token for a device
     */
    public static function storeDeviceToken(
        string $deviceId,
        string $fcmToken,
        int $userId,
        ?string $deviceName = null,
        ?string $platform = null
    ): self {
        return self::updateOrCreate(
            ['device_id' => $deviceId],
            [
                'fcm_token' => $fcmToken,
                'last_user_id' => $userId,
                'device_name' => $deviceName,
                'platform' => $platform,
                'last_used_at' => now(),
                'is_active' => true,
            ]
        );
    }

    /**
     * Mark token as inactive
     */
    public function markAsInactive(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Update last used timestamp
     */
    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
