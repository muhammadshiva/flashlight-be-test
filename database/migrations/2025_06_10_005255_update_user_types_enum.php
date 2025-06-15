<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, we need to modify the ENUM values first
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN type ENUM('owner', 'admin', 'cashier', 'staff', 'customer') NOT NULL DEFAULT 'customer'");
        }

        // Update any admin users to have the 'owner' type if they are the first admin
        $firstAdmin = DB::table('users')->where('type', 'admin')->orderBy('id', 'asc')->first();
        if ($firstAdmin) {
            DB::table('users')->where('id', $firstAdmin->id)->update(['type' => 'owner']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert owner users back to admin
        DB::table('users')->where('type', 'owner')->update(['type' => 'admin']);

        // Revert cashier users back to staff
        DB::table('users')->where('type', 'cashier')->update(['type' => 'staff']);

        // For MySQL, modify the ENUM values back
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN type ENUM('admin', 'staff', 'customer') NOT NULL DEFAULT 'customer'");
        }
    }
};
