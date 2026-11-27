<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $staff = User::where('restaurant_id', $restaurantId)
            ->whereHas('role', fn ($query) => $query->whereIn('slug', ['cashier', 'kitchen_staff']))
            ->with('role')
            ->orderBy('name')
            ->get();

        return response()->json(['staff' => $staff]);
    }

    public function store(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'role_id' => ['required', $this->staffRoleRule($restaurantId)],
            'pin_code' => ['required', 'digits:4', Rule::unique('users', 'pin_code')->where('restaurant_id', $restaurantId)],
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['restaurant_id'] = $restaurantId;
        $validated['is_active'] ??= true;
        $validated['email'] = 'staff-'.Str::uuid().'@pos.local';
        $validated['password'] = Hash::make(Str::random(40));

        $user = User::create($validated);

        return response()->json(['staff' => $user->load('role')], 201);
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->restaurant_id !== $request->user()->restaurant_id, 403);
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'role_id' => ['sometimes', $this->staffRoleRule($request->user()->restaurant_id)],
            'is_active' => 'boolean',
            'pin_code' => ['sometimes', 'required', 'digits:4', Rule::unique('users', 'pin_code')->where('restaurant_id', $request->user()->restaurant_id)->ignore($user->id)],
        ]);
        $user->update($validated);

        return response()->json(['staff' => $user->load('role')]);
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->restaurant_id !== $request->user()->restaurant_id, 403);
        abort_if($user->is($request->user()), 422, 'You cannot delete your own account.');

        $user->delete();

        return response()->json(['message' => 'Staff member deleted.']);
    }

    public function roles(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $roles = Role::where('restaurant_id', $restaurantId)
            ->whereIn('slug', ['cashier', 'kitchen_staff'])
            ->with('permissions')
            ->get();

        return response()->json(['roles' => $roles]);
    }

    private function staffRoleRule(int $restaurantId): Exists
    {
        return Rule::exists('roles', 'id')
            ->where('restaurant_id', $restaurantId)
            ->whereIn('slug', ['cashier', 'kitchen_staff']);
    }

    public function permissions()
    {
        $permissions = Permission::orderBy('module')->orderBy('name')->get();

        return response()->json(['permissions' => $permissions->groupBy('module')]);
    }

    public function updateRolePermissions(Request $request, Role $role)
    {
        abort_if($role->restaurant_id !== $request->user()->restaurant_id, 403);
        $validated = $request->validate(['permission_ids' => 'required|array', 'permission_ids.*' => 'exists:permissions,id']);
        $role->permissions()->sync($validated['permission_ids']);

        return response()->json(['role' => $role->load('permissions')]);
    }
}
