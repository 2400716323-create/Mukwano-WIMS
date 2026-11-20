<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    public function test_all_six_roles_are_defined(): void
    {
        $roles = [
            User::ROLE_ADMINISTRATOR,
            User::ROLE_WAREHOUSE_MANAGER,
            User::ROLE_STOREKEEPER,
            User::ROLE_PROCUREMENT_OFFICER,
            User::ROLE_DISPATCH_OFFICER,
            User::ROLE_AUDITOR,
        ];

        foreach ($roles as $role) {
            $this->assertArrayHasKey($role, config('roles.roles'));
        }

        $this->assertCount(6, $roles);
    }

    public function test_each_role_receives_its_configured_permissions(): void
    {
        foreach (config('roles.roles') as $role => $details) {
            $user = new User(['role' => $role]);

            foreach ($details['permissions'] as $permission) {
                $this->assertTrue(
                    $user->hasPermission($permission),
                    "{$role} should have permission: {$permission}"
                );
            }
        }
    }

    public function test_non_administrator_roles_do_not_receive_unassigned_permissions(): void
    {
        foreach (config('roles.roles') as $role => $details) {
            if ($role === User::ROLE_ADMINISTRATOR) {
                continue;
            }

            $user = new User(['role' => $role]);

            foreach (config('roles.roles') as $otherRole => $otherDetails) {
                if ($otherRole === $role) {
                    continue;
                }

                foreach ($otherDetails['permissions'] as $permission) {
                    if (! in_array($permission, $details['permissions'], true)) {
                        $this->assertFalse(
                            $user->hasPermission($permission),
                            "{$role} must not have unassigned permission: {$permission}"
                        );
                    }
                }
            }
        }
    }

    public function test_administrator_has_all_permissions(): void
    {
        $administrator = new User([
            'role' => User::ROLE_ADMINISTRATOR,
        ]);

        foreach (config('roles.roles') as $details) {
            foreach ($details['permissions'] as $permission) {
                $this->assertTrue($administrator->hasPermission($permission));
            }
        }

        $this->assertTrue(
            $administrator->hasPermission('An additional system permission')
        );
    }

    public function test_auditor_has_read_only_permissions(): void
    {
        $auditor = new User(['role' => User::ROLE_AUDITOR]);

        $this->assertTrue(
            $auditor->hasPermission(
                'View stock enquiry, stock ledger, and movements history'
            )
        );

        $this->assertFalse(
            $auditor->hasPermission(
                'Receive, transfer, dispatch, and reconcile stock'
            )
        );

        $this->assertFalse(
            $auditor->hasPermission(
                'Create and approve cycle counts and adjust variances'
            )
        );

        $this->assertFalse(
            $auditor->hasPermission(
                'Manage users, roles, and security policies'
            )
        );
    }

    public function test_legacy_role_names_are_supported(): void
    {
        $this->assertTrue(
            (new User(['role' => 'admin']))->hasPermission(
                'Manage users, roles, and security policies'
            )
        );

        $this->assertTrue(
            (new User(['role' => 'supervisor']))->hasRole(
                User::ROLE_WAREHOUSE_MANAGER
            )
        );

        $this->assertTrue(
            (new User(['role' => 'user']))->hasRole(
                User::ROLE_STOREKEEPER
            )
        );
    }

    public function test_all_six_roles_can_view_categories_and_products(): void
    {
        $roles = [
            User::ROLE_ADMINISTRATOR,
            User::ROLE_WAREHOUSE_MANAGER,
            User::ROLE_STOREKEEPER,
            User::ROLE_PROCUREMENT_OFFICER,
            User::ROLE_DISPATCH_OFFICER,
            User::ROLE_AUDITOR,
        ];

        foreach ($roles as $role) {
            $user = new User(['role' => $role]);

            $this->assertTrue(
                $user->can('viewAny', Category::class),
                "{$role} should be able to view categories."
            );

            $this->assertTrue(
                $user->can('viewAny', Product::class),
                "{$role} should be able to view products."
            );
        }
    }

    public function test_only_administrator_and_warehouse_manager_can_manage_categories(): void
    {
        $allowedRoles = [
            User::ROLE_ADMINISTRATOR,
            User::ROLE_WAREHOUSE_MANAGER,
        ];

        foreach (config('roles.roles') as $role => $details) {
            $user = new User(['role' => $role]);
            $allowed = in_array($role, $allowedRoles, true);

            $this->assertSame(
                $allowed,
                $user->can('create', Category::class),
                "{$role} category creation permission is incorrect."
            );

            $this->assertSame(
                $allowed,
                $user->can('update', new Category),
                "{$role} category update permission is incorrect."
            );
        }
    }

    public function test_storekeeper_can_create_products_but_cannot_edit_them(): void
    {
        $storekeeper = new User(['role' => User::ROLE_STOREKEEPER]);
        $product = new Product;

        $this->assertTrue($storekeeper->can('create', Product::class));
        $this->assertFalse($storekeeper->can('update', $product));

        $this->assertTrue(
            (new User(['role' => User::ROLE_ADMINISTRATOR]))
                ->can('create', Product::class)
        );

        $this->assertTrue(
            (new User(['role' => User::ROLE_WAREHOUSE_MANAGER]))
                ->can('update', $product)
        );

        $this->assertFalse(
            (new User(['role' => User::ROLE_PROCUREMENT_OFFICER]))
                ->can('create', Product::class)
        );
    }

    public function test_supplier_management_permissions_are_separated_from_deletion(): void
    {
        $supplier = new Supplier;

        $expected = [
            User::ROLE_ADMINISTRATOR => [true, true, true, true],
            User::ROLE_WAREHOUSE_MANAGER => [true, true, true, true],
            User::ROLE_STOREKEEPER => [false, false, false, false],
            User::ROLE_PROCUREMENT_OFFICER => [true, true, true, false],
            User::ROLE_DISPATCH_OFFICER => [false, false, false, false],
            User::ROLE_AUDITOR => [false, false, false, false],
        ];

        foreach ($expected as $role => [$view, $create, $update, $delete]) {
            $user = new User(['role' => $role]);

            $this->assertSame(
                $view,
                $user->can('viewAny', Supplier::class),
                "{$role} supplier viewing permission is incorrect."
            );

            $this->assertSame(
                $create,
                $user->can('create', Supplier::class),
                "{$role} supplier creation permission is incorrect."
            );

            $this->assertSame(
                $update,
                $user->can('update', $supplier),
                "{$role} supplier update permission is incorrect."
            );

            $this->assertSame(
                $delete,
                $user->can('delete', $supplier),
                "{$role} supplier deletion permission is incorrect."
            );
        }
    }
}
