<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\TaxCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::create([
            'name' => 'Restaurant Group',
            'slug' => 'restaurant-group',
            'email' => 'admin@restaurantpos.com',
            'phone' => '9999999999',
            'subscription_plan' => 'premium',
            'is_active' => true,
        ]);

        $restaurant = Restaurant::create([
            'organization_id' => $org->id,
            'name' => 'Pakizah Resturant - Main Branch',
            'code' => 'PPM',
            'slug' => 'restaurant-main',
            'email' => 'main@restaurantpos.com',
            'phone' => '076543210',
            'address' => '123 kabul , Afghanistan',
            'city' => 'Kabul',
            'state' => 'Kabul',
            'pincode' => '560034',
            'gst_number' => '29AABCT1332L1ZN',
            'fssai_license' => '11225003004071',
            'currency_symbol' => 'AFS',
            'timezone' => 'Asia/Kolkata',
            'operation_start_time' => '09:00:00',
            'operation_end_time' => '23:00:00',
            'is_active' => true,
            'settings' => ['service_charge_percentage' => 5, 'print_kot' => true, 'print_bill' => true],
        ]);

        // Permissions
        $permList = [
            ['dashboard', 'View Dashboard', 'dashboard.view'],
            ['orders', 'View Orders', 'orders.view'],
            ['orders', 'Create Orders', 'orders.create'],
            ['orders', 'Edit Orders', 'orders.edit'],
            ['orders', 'Delete Orders', 'orders.delete'],
            ['orders', 'Cancel Orders', 'orders.cancel'],
            ['orders', 'Apply Discounts', 'orders.discount'],
            ['payments', 'Process Payments', 'payments.process'],
            ['payments', 'View Payments', 'payments.view'],
            ['payments', 'Refund Payments', 'payments.refund'],
            ['menu', 'View Menu', 'menu.view'],
            ['menu', 'Create Products', 'menu.create'],
            ['menu', 'Edit Products', 'menu.edit'],
            ['menu', 'Delete Products', 'menu.delete'],
            ['menu', 'Manage Categories', 'menu.categories'],
            ['tables', 'Manage Tables', 'tables.manage'],
            ['tables', 'View Tables', 'tables.view'],
            ['inventory', 'View Inventory', 'inventory.view'],
            ['inventory', 'Manage Inventory', 'inventory.manage'],
            ['inventory', 'Purchase Orders', 'inventory.purchase'],
            ['staff', 'View Staff', 'staff.view'],
            ['staff', 'Manage Staff', 'staff.manage'],
            ['reports', 'View Reports', 'reports.view'],
            ['reports', 'Export Reports', 'reports.export'],
            ['reports', 'Financial Reports', 'reports.financial'],
            ['settings', 'View Settings', 'settings.view'],
            ['settings', 'Manage Settings', 'settings.manage'],
            ['settings', 'Manage Taxes', 'settings.taxes'],
            ['customers', 'View Customers', 'customers.view'],
            ['customers', 'Manage Customers', 'customers.manage'],
            ['kitchen', 'View KDS', 'kitchen.view'],
            ['kitchen', 'Manage KDS', 'kitchen.manage'],
            ['kitchen', 'Update Order Status', 'kitchen.update_status'],
        ];

        $permissionIds = [];
        foreach ($permList as [$module, $name, $slug]) {
            $p = Permission::create(['module' => $module, 'name' => $name, 'slug' => $slug]);
            $permissionIds[$slug] = $p->id;
        }

        $adminRole = Role::create(['restaurant_id' => $restaurant->id, 'name' => 'Restaurant Admin', 'slug' => 'admin', 'is_system' => true]);
        $managerRole = Role::create(['restaurant_id' => $restaurant->id, 'name' => 'Manager', 'slug' => 'manager', 'is_system' => true]);
        $cashierRole = Role::create(['restaurant_id' => $restaurant->id, 'name' => 'Cashier', 'slug' => 'cashier', 'is_system' => true]);
        $waiterRole = Role::create(['restaurant_id' => $restaurant->id, 'name' => 'Waiter', 'slug' => 'waiter', 'is_system' => true]);
        $kitchenRole = Role::create(['restaurant_id' => $restaurant->id, 'name' => 'Kitchen Staff', 'slug' => 'kitchen_staff', 'is_system' => true]);

        $adminRole->permissions()->sync(array_values($permissionIds));

        $cashierPerms = ['orders.view', 'orders.create', 'orders.edit', 'orders.discount', 'menu.view', 'tables.view'];
        $cashierRole->permissions()->sync(array_values(array_intersect_key($permissionIds, array_flip($cashierPerms))));

        $waiterPerms = ['dashboard.view', 'orders.view', 'orders.create', 'orders.edit', 'menu.view', 'tables.view', 'customers.view'];
        $waiterRole->permissions()->sync(array_values(array_intersect_key($permissionIds, array_flip($waiterPerms))));

        $kitchenPerms = ['kitchen.view', 'kitchen.manage', 'kitchen.update_status'];
        $kitchenRole->permissions()->sync(array_values(array_intersect_key($permissionIds, array_flip($kitchenPerms))));

        $managerPerms = array_values(array_diff_key($permissionIds, ['payments.process' => true]));
        $managerRole->permissions()->sync($managerPerms);

        User::create(['restaurant_id' => $restaurant->id, 'role_id' => $adminRole->id, 'name' => 'Admin User', 'email' => 'admin@restaurantpos.com', 'password' => Hash::make('password123'), 'phone' => '9876543210', 'employee_code' => 'EMP001', 'pin_code' => '1234', 'date_of_joining' => now()->toDateString(), 'is_active' => true]);
        User::create(['restaurant_id' => $restaurant->id, 'role_id' => $cashierRole->id, 'name' => 'Cashier User', 'email' => 'cashier@restaurantpos.com', 'password' => Hash::make('password123'), 'phone' => '9876543211', 'employee_code' => 'EMP002', 'pin_code' => '5678', 'date_of_joining' => now()->toDateString(), 'is_active' => true]);
        User::create(['restaurant_id' => $restaurant->id, 'role_id' => $kitchenRole->id, 'name' => 'Kitchen Staff', 'email' => 'kitchen@restaurantpos.com', 'password' => Hash::make('password123'), 'phone' => '9876543212', 'employee_code' => 'EMP003', 'pin_code' => '9012', 'is_active' => true]);

        TaxCategory::create(['restaurant_id' => $restaurant->id, 'name' => 'GST 5%', 'tax_percentage' => 5, 'cgst_percentage' => 2.5, 'sgst_percentage' => 2.5, 'is_active' => true]);
        TaxCategory::create(['restaurant_id' => $restaurant->id, 'name' => 'GST 12%', 'tax_percentage' => 12, 'cgst_percentage' => 6, 'sgst_percentage' => 6, 'is_active' => true]);
        TaxCategory::create(['restaurant_id' => $restaurant->id, 'name' => 'GST 18%', 'tax_percentage' => 18, 'cgst_percentage' => 9, 'sgst_percentage' => 9, 'is_active' => true]);

        $this->call(MenuSeeder::class);

        $this->call(TableLayoutSeeder::class);

        $this->command->info('✅ Database seeded! Login: admin@restaurantpos.com / password123');
    }
}
