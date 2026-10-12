<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->orderBy('id', 'desc')->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => $users->items(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'total' => $users->total(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', Password::min(8)],
            'role' => ['required', 'string', 'exists:roles,name'],
            'is_active' => ['boolean'],
        ]);

        // Security check: Only super_admin can assign super_admin or roles they do not have
        /** @var User $currentUser */
        $currentUser = $request->user();
        if ($validated['role'] === 'super_admin' && ! $currentUser->hasRole('super_admin')) {
            return response()->json(['message' => 'Unauthorized to assign super_admin role'], 403);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($validated['role']);

        return response()->json([
            'message' => 'Staff user created successfully',
            'user' => $user->load('roles'),
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'user' => $user->load('roles'),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['sometimes', 'string', 'exists:roles,name'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // Protection: Super admin cannot deactivate themselves
        if ($user->id === $currentUser->id && isset($validated['is_active']) && ! $validated['is_active']) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 422);
        }

        if (isset($validated['role'])) {
            if ($validated['role'] === 'super_admin' && ! $currentUser->hasRole('super_admin')) {
                return response()->json(['message' => 'Unauthorized to assign super_admin role'], 403);
            }
            $user->syncRoles([$validated['role']]);
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $user->phone,
            'is_active' => $validated['is_active'] ?? $user->is_active,
        ]);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->fresh()->load('roles'),
        ]);
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', Password::min(8)],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'User password has been reset successfully',
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $request->user();

        if ($user->id === $currentUser->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        if ($user->hasRole('super_admin') && User::role('super_admin')->count() <= 1) {
            return response()->json(['message' => 'Cannot delete the sole super administrator.'], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
