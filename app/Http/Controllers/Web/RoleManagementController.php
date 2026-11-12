<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Menu;
use App\Models\Role;
use App\Models\RoleMenu;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleManagementController extends Controller
{
    public function index(Request $request): View
    {
        $roles = Role::withCount('users')->get();

        // Selected role for the matrix
        $selectedRoleId = (int) $request->input('role_id', $roles->where('role_code', 'admin')->first()?->id ?? $roles->first()?->id);
        $selectedRole   = Role::findOrFail($selectedRoleId);

        // All menus ordered
        $menus = Menu::ordered()->get();

        // Permissions map for selected role: [menu_id => RoleMenu]
        $rolePermissions = RoleMenu::where('role_id', $selectedRoleId)
            ->get()
            ->keyBy('menu_id');

        // All users with roles
        $users = User::with('roles')->orderBy('name')->paginate(10);

        return view('roles.index', compact(
            'roles',
            'selectedRole',
            'menus',
            'rolePermissions',
            'users'
        ));
    }

    public function updatePermissions(Request $request, int $roleId): RedirectResponse
    {
        $role = Role::findOrFail($roleId);

        // Prevent modifying super_admin permissions (always full access)
        if ($role->role_code === 'super_admin') {
            return redirect()->back()->with('error', 'Super Admin permissions are fixed and cannot be modified.');
        }

        $permissions = $request->input('permissions', []);
        $menus = Menu::all();

        foreach ($menus as $menu) {
            $menuPerms = $permissions[$menu->id] ?? [];

            RoleMenu::updateOrCreate(
                [
                    'role_id' => $role->id,
                    'menu_id' => $menu->id,
                ],
                [
                    'can_view'   => !empty($menuPerms['can_view']),
                    'can_create' => !empty($menuPerms['can_create']),
                    'can_update' => !empty($menuPerms['can_update']),
                    'can_delete' => !empty($menuPerms['can_delete']),
                    'can_export' => !empty($menuPerms['can_export']),
                ]
            );
        }

        AuditLog::record(
            $this->getUserId(),
            'UPDATE_PERMISSIONS',
            'md_role_menu',
            $role->id,
            null,
            $permissions
        );

        return redirect()->route('roles.index', ['role_id' => $role->id])
            ->with('success', "Permissions for role '{$role->role_name}' updated successfully.");
    }

    public function assignUserRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:md_role,id',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $role = Role::findOrFail($validated['role_id']);

        $user->roles()->sync([$role->id]);

        AuditLog::record(
            $this->getUserId(),
            'ASSIGN_ROLE',
            'user_roles',
            $user->id,
            null,
            ['user_id' => $user->id, 'role_id' => $role->id]
        );

        return redirect()->route('roles.index', ['role_id' => $role->id])
            ->with('success', "Role '{$role->role_name}' assigned to {$user->name}.");
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role_name'   => 'required|string|max:100|unique:md_role,role_name',
            'description' => 'nullable|string|max:255',
        ]);

        $roleCode = Str::slug($validated['role_name'], '_');

        $role = Role::create([
            'role_code'   => $roleCode,
            'role_name'   => $validated['role_name'],
            'description' => $validated['description'] ?? null,
            'is_active'   => true,
        ]);

        // Initialize default view-only permissions for new role
        $menus = Menu::all();
        foreach ($menus as $menu) {
            RoleMenu::create([
                'role_id'    => $role->id,
                'menu_id'    => $menu->id,
                'can_view'   => true,
                'can_create' => false,
                'can_update' => false,
                'can_delete' => false,
                'can_export' => false,
            ]);
        }

        AuditLog::record($this->getUserId(), 'CREATE_ROLE', 'md_role', $role->id, null, $role->toArray());

        return redirect()->route('roles.index', ['role_id' => $role->id])
            ->with('success', "Role '{$role->role_name}' created successfully.");
    }

    public function destroyRole(int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        if (in_array($role->role_code, ['super_admin', 'admin', 'user'])) {
            return redirect()->back()->with('error', 'Default system roles cannot be deleted.');
        }

        $oldValues = $role->toArray();
        $role->delete();

        AuditLog::record($this->getUserId(), 'DELETE_ROLE', 'md_role', $id, $oldValues, null);

        return redirect()->route('roles.index')
            ->with('success', "Role '{$role->role_name}' deleted successfully.");
    }
}

