<?php

namespace Database\Seeders;

use App\Models\MembershipType;
use Illuminate\Database\Seeder;

class MembershipTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Basic Membership
        MembershipType::create([
            'name' => 'Basic',
            'benefits' => [
                '10% discount on all services',
                'Priority booking',
            ],
            'is_active' => true,
        ]);

        // Premium Membership
        MembershipType::create([
            'name' => 'Premium',
            'benefits' => [
                '20% discount on all services',
                'Priority booking',
                'Free home service',
                'Free delivery',
                'Free food & drinks',
            ],
            'is_active' => true,
        ]);

        // VIP Membership
        MembershipType::create([
            'name' => 'VIP',
            'benefits' => [
                '30% discount on all services',
                'Priority booking',
                'Free home service',
                'Free delivery',
                'Free food & drinks',
                'Exclusive VIP lounge access',
                'Personal car care consultant',
            ],
            'is_active' => true,
        ]);
    }
}
