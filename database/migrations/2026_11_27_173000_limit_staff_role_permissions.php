<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->syncRolePermissions('cashier', [
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.discount',
            'menu.view',
            'tables.view',
        ]);
        $this->syncRolePermissions('kitchen_staff', [
            'kitchen.view',
            'kitchen.manage',
            'kitchen.update_status',
        ]);

        $this->removePermissionFromRole('manager', 'payments.process');
    }

    public function down(): void
    {
        $this->syncRolePermissions('cashier', [
            'dashboard.view',
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.discount',
            'payments.process',
            'payments.view',
            'menu.view',
            'tables.view',
            'customers.view',
            'customers.manage',
        ]);
        $this->syncRolePermissions('kitchen_staff', [
            'kitchen.view',
            'kitchen.manage',
            'kitchen.update_status',
            'orders.view',
        ]);

        $this->addPermissionToRole('manager', 'payments.process');
    }

    private function syncRolePermissions(string $roleSlug, array $permissionSlugs): void
    {
        $roleIds = DB::table('roles')->where('slug', $roleSlug)->pluck('id');
        $permissionIds = DB::table('permissions')->whereIn('slug', $permissionSlugs)->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->where('role_id', $roleId)->delete();

            if ($permissionIds->isNotEmpty()) {
                DB::table('role_permissions')->insert(
                    $permissionIds->map(fn ($permissionId) => [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ])->all()
                );
            }
        }
    }

    private function removePermissionFromRole(string $roleSlug, string $permissionSlug): void
    {
        $permissionId = DB::table('permissions')->where('slug', $permissionSlug)->value('id');
        if (! $permissionId) {
            return;
        }

        DB::table('role_permissions')
            ->whereIn('role_id', DB::table('roles')->where('slug', $roleSlug)->select('id'))
            ->where('permission_id', $permissionId)
            ->delete();
    }

    private function addPermissionToRole(string $roleSlug, string $permissionSlug): void
    {
        $permissionId = DB::table('permissions')->where('slug', $permissionSlug)->value('id');
        if (! $permissionId) {
            return;
        }

        $roleIds = DB::table('roles')->where('slug', $roleSlug)->pluck('id');
        foreach ($roleIds as $roleId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }
};
