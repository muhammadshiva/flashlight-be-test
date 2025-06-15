<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The available user types.
     */
    const TYPE_OWNER = 'owner';
    const TYPE_ADMIN = 'admin';
    const TYPE_CASHIER = 'cashier';
    const TYPE_STAFF = 'staff';
    const TYPE_CUSTOMER = 'customer';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'password',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'profile_image',
        'membership_type_id',
        'membership_expires_at',
        'is_active',
        'last_login_at',
        'type',
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'membership_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::created(function (User $user) {
            // Assign role based on user type
            switch ($user->type) {
                case self::TYPE_OWNER:
                    $user->assignRole('owner');
                    break;
                case self::TYPE_ADMIN:
                    $user->assignRole('admin');
                    break;
                case self::TYPE_CASHIER:
                    $user->assignRole('cashier');
                    break;
                case self::TYPE_STAFF:
                    $user->assignRole('staff');
                    break;
                case self::TYPE_CUSTOMER:
                    $user->assignRole('customer');
                    break;
            }
        });

        static::updated(function (User $user) {
            // If user type changed, update roles
            if ($user->isDirty('type')) {
                // Remove existing roles
                $user->syncRoles([]);

                // Assign new role based on updated type
                switch ($user->type) {
                    case self::TYPE_OWNER:
                        $user->assignRole('owner');
                        break;
                    case self::TYPE_ADMIN:
                        $user->assignRole('admin');
                        break;
                    case self::TYPE_CASHIER:
                        $user->assignRole('cashier');
                        break;
                    case self::TYPE_STAFF:
                        $user->assignRole('staff');
                        break;
                    case self::TYPE_CUSTOMER:
                        $user->assignRole('customer');
                        break;
                }
            }
        });
    }

    /**
     * Get all available user types.
     *
     * @return array<string, string>
     */
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_OWNER => 'Owner',
            self::TYPE_ADMIN => 'Admin',
            self::TYPE_CASHIER => 'Cashier',
            self::TYPE_STAFF => 'Staff',
            self::TYPE_CUSTOMER => 'Customer',
        ];
    }

    public function membershipType(): BelongsTo
    {
        return $this->belongsTo(MembershipType::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(CustomerVehicle::class);
    }

    public function washTransactions(): HasMany
    {
        return $this->hasMany(WashTransaction::class);
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function isOwner(): bool
    {
        return $this->type === self::TYPE_OWNER;
    }

    public function isAdmin(): bool
    {
        return $this->type === self::TYPE_ADMIN;
    }

    public function isCashier(): bool
    {
        return $this->type === self::TYPE_CASHIER;
    }

    public function isStaff(): bool
    {
        return $this->type === self::TYPE_STAFF;
    }

    public function isCustomer(): bool
    {
        return $this->type === self::TYPE_CUSTOMER;
    }

    public function canAccessAdmin(): bool
    {
        return in_array($this->type, [self::TYPE_OWNER, self::TYPE_ADMIN, self::TYPE_CASHIER]);
    }
}
