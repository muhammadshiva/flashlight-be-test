<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Clear existing roles and permissions to avoid duplicates
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create permissions
        // General permissions
        Permission::create(['name' => 'view dashboard']);

        // User permissions
        Permission::create(['name' => 'create users']);
        Permission::create(['name' => 'view users']);
        Permission::create(['name' => 'edit users']);
        Permission::create(['name' => 'delete users']);

        // Customer permissions
        Permission::create(['name' => 'create customers']);
        Permission::create(['name' => 'view customers']);
        Permission::create(['name' => 'edit customers']);
        Permission::create(['name' => 'delete customers']);

        // Product permissions
        Permission::create(['name' => 'create products']);
        Permission::create(['name' => 'view products']);
        Permission::create(['name' => 'edit products']);
        Permission::create(['name' => 'delete products']);

        // Product Category permissions
        Permission::create(['name' => 'create product categories']);
        Permission::create(['name' => 'view product categories']);
        Permission::create(['name' => 'edit product categories']);
        Permission::create(['name' => 'delete product categories']);

        // Staff permissions
        Permission::create(['name' => 'create staff']);
        Permission::create(['name' => 'view staff']);
        Permission::create(['name' => 'edit staff']);
        Permission::create(['name' => 'delete staff']);

        // Vehicle permissions
        Permission::create(['name' => 'create vehicles']);
        Permission::create(['name' => 'view vehicles']);
        Permission::create(['name' => 'edit vehicles']);
        Permission::create(['name' => 'delete vehicles']);

        // Customer Vehicle permissions
        Permission::create(['name' => 'create customer vehicles']);
        Permission::create(['name' => 'view customer vehicles']);
        Permission::create(['name' => 'edit customer vehicles']);
        Permission::create(['name' => 'delete customer vehicles']);

        // Membership Type permissions
        Permission::create(['name' => 'create membership types']);
        Permission::create(['name' => 'view membership types']);
        Permission::create(['name' => 'edit membership types']);
        Permission::create(['name' => 'delete membership types']);

        // Wash Transaction permissions
        Permission::create(['name' => 'create wash transactions']);
        Permission::create(['name' => 'view wash transactions']);
        Permission::create(['name' => 'edit wash transactions']);
        Permission::create(['name' => 'delete wash transactions']);

        // Create roles and assign permissions

        // Create owner role with all permissions
        $ownerRole = Role::create(['name' => 'owner']);
        $ownerRole->givePermissionTo(Permission::all());

        // Create admin role with all permissions except wash transaction management
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all()->except(
            Permission::where('name', 'create wash transactions')->first()->id,
            Permission::where('name', 'edit wash transactions')->first()->id,
            Permission::where('name', 'delete wash transactions')->first()->id
        ));

        // Create cashier role with only view transactions permission
        $cashierRole = Role::create(['name' => 'cashier']);
        $cashierRole->givePermissionTo([
            'view dashboard',
            'view wash transactions',
            'view customers',
            'view products',
            'view customer vehicles',
        ]);

        // Create staff role with limited permissions
        // Staff shouldn't access the admin dashboard
        $staffRole = Role::create(['name' => 'staff']);
        $staffRole->givePermissionTo([
            'view wash transactions',
            'create wash transactions',
            'edit wash transactions',
        ]);

        // Create customer role with very limited permissions
        // Customers shouldn't access the admin dashboard
        $customerRole = Role::create(['name' => 'customer']);
        $customerRole->givePermissionTo([
            'view wash transactions',
        ]);

        // Link user types to roles
        // The next time a user is created, we'll assign them the appropriate role based on their type
        $this->assignDefaultRolesToExistingUsers();
    }

    /**
     * Assign default roles to existing users based on their type.
     */
    private function assignDefaultRolesToExistingUsers(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            // Remove any existing roles first
            $user->syncRoles([]);

            // Assign new role based on user type with explicit web guard
            switch ($user->type) {
                case User::TYPE_OWNER:
                    $role = Role::findByName('owner', 'web');
                    $user->assignRole($role);
                    break;
                case User::TYPE_ADMIN:
                    $role = Role::findByName('admin', 'web');
                    $user->assignRole($role);
                    break;
                case User::TYPE_CASHIER:
                    $role = Role::findByName('cashier', 'web');
                    $user->assignRole($role);
                    break;
                case User::TYPE_STAFF:
                    $role = Role::findByName('staff', 'web');
                    $user->assignRole($role);
                    break;
                case User::TYPE_CUSTOMER:
                    $role = Role::findByName('customer', 'web');
                    $user->assignRole($role);
                    break;
            }
        }
    }
}
