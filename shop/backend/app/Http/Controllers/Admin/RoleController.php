<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    protected array $coreRoles = [
        'super_admin',
        'product_manager',
        'order_manager',
        'support_agent',
        'finance_manager',
        'customer',
    ];

    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->withCount('users')->get();

        return response()->json([
            'data' => $roles->map(function (Role $role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'is_core' => in_array($role->name, $this->coreRoles),
                    'users_count' => $role->users_count,
                    'permissions' => $role->permissions->pluck('name')->toArray(),
                ];
            }),
        ]);
    }

    public function permissions(): JsonResponse
    {
        $permissions = Permission::all();

        // Group permissions by category for the UI checkbox matrix
        $groups = [
            'Products' => ['products.view', 'products.create', 'products.update', 'products.delete', 'categories.manage', 'brands.manage'],
            'Orders & Sales' => ['orders.view', 'orders.update', 'orders.refund', 'coupons.manage', 'shipping.manage'],
            'Finance' => ['finance.view', 'reports.view'],
            'Settings & Config' => ['settings.view', 'settings.update', 'stripe.manage', 'mail.manage'],
            'Staff & Roles' => ['users.view', 'users.create', 'users.update', 'roles.manage'],
            'Marketing & Content' => ['hero.manage', 'popups.manage', 'menus.manage', 'pages.manage'],
            'Support & Reviews' => ['chat.manage', 'reviews.manage', 'activity.view'],
        ];

        return response()->json([
            'all' => $permissions->pluck('name'),
            'groups' => $groups,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $roleSlug = Str::slug($validated['name'], '_');

        if (Role::where('name', $roleSlug)->exists()) {
            return response()->json(['message' => 'A role with this identifier already exists.'], 422);
        }

        $role = Role::create(['name' => $roleSlug, 'guard_name' => 'web']);
        $role->syncPermissions($validated['permissions']);

        return response()->json([
            'message' => 'Role created successfully',
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'is_core' => false,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ],
        ], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        if ($role->name === 'super_admin') {
            return response()->json(['message' => 'The super_admin role permissions cannot be modified.'], 422);
        }

        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($validated['permissions']);

        return response()->json([
            'message' => 'Role permissions updated successfully',
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'is_core' => in_array($role->name, $this->coreRoles),
                'permissions' => $role->fresh()->permissions->pluck('name')->toArray(),
            ],
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        if (in_array($role->name, $this->coreRoles)) {
            return response()->json(['message' => 'System core roles cannot be deleted.'], 422);
        }

        if ($role->users()->count() > 0) {
            return response()->json(['message' => 'Cannot delete role because it is currently assigned to users.'], 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully',
        ]);
    }
}
