# Role and Permission System

This application uses Spatie's Laravel Permission package to manage role-based access control.

## Available Roles

1. **Owner**: Can perform all operations without any restrictions
2. **Admin**: Can perform all operations except for creating, editing, and deleting wash transactions
3. **Cashier**: Can only view the dashboard, wash transactions, customers, products, and customer vehicles

## Available Permissions

The system includes the following permission groups:

-   **General**: view dashboard
-   **Users**: create, view, edit, delete
-   **Customers**: create, view, edit, delete
-   **Products**: create, view, edit, delete
-   **Product Categories**: create, view, edit, delete
-   **Staff**: create, view, edit, delete
-   **Vehicles**: create, view, edit, delete
-   **Customer Vehicles**: create, view, edit, delete
-   **Membership Types**: create, view, edit, delete
-   **Wash Transactions**: create, view, edit, delete

## Usage

### Assigning Roles

You can assign roles to users using the provided Artisan command:

```bash
php artisan app:assign-role {userId} {roleName}
```

Example:

```bash
php artisan app:assign-role 1 admin
```

### Using Middleware in Routes

To protect a route with role-based access control, use the `role` middleware:

```php
Route::get('/admin/dashboard', function () {
    // Only users with the 'admin' role can access this route
})->middleware('role:admin');
```

To protect a route with permission-based access control, use the `permission` middleware:

```php
Route::get('/wash-transactions', function () {
    // Only users with the 'view wash transactions' permission can access this route
})->middleware('permission:view wash transactions');
```

### Checking Roles and Permissions in Code

You can check if a user has a specific role:

```php
if ($user->hasRole('admin')) {
    // User has the admin role
}
```

You can check if a user has a specific permission:

```php
if ($user->can('edit customers')) {
    // User has permission to edit customers
}
```

## Super Admin (Owner) Access

The 'owner' role is set up as a super admin in the `AuthServiceProvider`. This means that users with the 'owner' role will automatically pass all permission checks.

## Seeding Roles and Permissions

To reseed the roles and permissions, run:

```bash
php artisan db:seed --class=RolePermissionSeeder
```
