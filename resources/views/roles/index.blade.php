<x-layouts.app title="Roles & Access Control">
    @php
        $roleOptions = [];
        foreach ($roles as $r) {
            $roleOptions[$r->id] = [
                'label'    => $r->role_name,
                'badge'    => $r->role_code,
                'sublabel' => $r->description ?? '',
            ];
        }
    @endphp

    <div x-data="{ openRoleModal: false, assignModal: false, targetUser: {} }">

        <x-page-header
            title="Roles & Access Control"
            subtitle="Manage Role-Based Access Control (RBAC), permissions matrix, and user assignments."
            :breadcrumbs="[
                ['label' => 'Dashboard', 'url' => route('dashboard')],
                ['label' => 'Roles & Access', 'url' => route('roles.index')],
            ]">
            <x-slot:actions>
                <x-button variant="primary" size="sm" @click="openRoleModal = true">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Role
                </x-button>
            </x-slot:actions>
        </x-page-header>

        {{-- Roles Cards Directory --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            @foreach($roles as $role)
                <div class="card p-5 relative overflow-hidden transition hover:shadow-md border-l-4 {{ $selectedRole->id === $role->id ? 'ring-2 ring-emerald-500 border-l-emerald-600' : 'border-l-gray-300' }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="inline-block px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider rounded bg-gray-100 text-gray-700 mb-1">
                                {{ $role->role_code }}
                            </span>
                            <h3 class="text-base font-bold text-gray-900">{{ $role->role_name }}</h3>
                            <p class="text-xs text-gray-400 mt-1 line-clamp-2">{{ $role->description ?: 'No description provided.' }}</p>
                        </div>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">
                            {{ $role->users_count }} user(s)
                        </span>
                    </div>

                    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                        <a href="{{ route('roles.index', ['role_id' => $role->id]) }}"
                           class="text-xs font-semibold {{ $selectedRole->id === $role->id ? 'text-emerald-600' : 'text-gray-500 hover:text-emerald-600' }}">
                            {{ $selectedRole->id === $role->id ? '&bull; Currently Editing' : 'Configure Matrix &rarr;' }}
                        </a>

                        @if(!in_array($role->role_code, ['super_admin', 'admin', 'user']))
                            <form action="{{ route('roles.destroy', $role->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="button"
                                    @click="$confirm({
                                        title: 'Delete role {{ $role->role_name }}?',
                                        description: 'Are you sure you want to delete this custom role? Any users assigned to this role will lose their granted permissions.',
                                        confirmText: 'Delete Role',
                                        cancelText: 'Cancel',
                                        variant: 'danger',
                                        form: $el.closest('form')
                                    })"
                                    class="text-xs text-red-500 hover:text-red-700 font-medium cursor-pointer">
                                    Delete
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Permission Matrix Section --}}
        <div class="card mb-8">
            <div class="card-header flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/50">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-sm font-semibold text-gray-900">Permission Matrix</h2>
                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-emerald-100 text-emerald-800">
                            {{ $selectedRole->role_name }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-400 mt-0.5">Control menu access and operational permissions (view, create, update, delete, export)</p>
                </div>

                @if($selectedRole->role_code === 'super_admin')
                    <span class="text-xs text-emerald-600 font-semibold bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">
                        &check; Super Admin retains unrestricted access across all menus
                    </span>
                @endif
            </div>

            <div class="card-body p-0">
                <form action="{{ route('roles.update-permissions', $selectedRole->id) }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 border-b border-gray-200 text-gray-600 font-semibold">
                                <tr>
                                    <th class="px-5 py-3.5 w-1/3">Menu / Module</th>
                                    <th class="px-4 py-3.5 text-center">Can View</th>
                                    <th class="px-4 py-3.5 text-center">Can Create</th>
                                    <th class="px-4 py-3.5 text-center">Can Update</th>
                                    <th class="px-4 py-3.5 text-center">Can Delete</th>
                                    <th class="px-4 py-3.5 text-center">Can Export</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($menus as $menu)
                                    @php
                                        $perm = $rolePermissions[$menu->id] ?? null;
                                        $isSuper = $selectedRole->role_code === 'super_admin';
                                    @endphp
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3.5">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-gray-900">{{ $menu->menu_name }}</span>
                                                <code class="text-[11px] px-1.5 py-0.5 bg-gray-100 text-gray-500 rounded">{{ $menu->menu_url }}</code>
                                            </div>
                                        </td>

                                        {{-- View --}}
                                        <td class="px-4 py-3.5 text-center">
                                            <input type="checkbox"
                                                   name="permissions[{{ $menu->id }}][can_view]"
                                                   value="1"
                                                   {{ ($isSuper || ($perm && $perm->can_view)) ? 'checked' : '' }}
                                                   {{ $isSuper ? 'disabled' : '' }}
                                                   class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                        </td>

                                        {{-- Create --}}
                                        <td class="px-4 py-3.5 text-center">
                                            <input type="checkbox"
                                                   name="permissions[{{ $menu->id }}][can_create]"
                                                   value="1"
                                                   {{ ($isSuper || ($perm && $perm->can_create)) ? 'checked' : '' }}
                                                   {{ $isSuper ? 'disabled' : '' }}
                                                   class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                        </td>

                                        {{-- Update --}}
                                        <td class="px-4 py-3.5 text-center">
                                            <input type="checkbox"
                                                   name="permissions[{{ $menu->id }}][can_update]"
                                                   value="1"
                                                   {{ ($isSuper || ($perm && $perm->can_update)) ? 'checked' : '' }}
                                                   {{ $isSuper ? 'disabled' : '' }}
                                                   class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                        </td>

                                        {{-- Delete --}}
                                        <td class="px-4 py-3.5 text-center">
                                            <input type="checkbox"
                                                   name="permissions[{{ $menu->id }}][can_delete]"
                                                   value="1"
                                                   {{ ($isSuper || ($perm && $perm->can_delete)) ? 'checked' : '' }}
                                                   {{ $isSuper ? 'disabled' : '' }}
                                                   class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                        </td>

                                        {{-- Export --}}
                                        <td class="px-4 py-3.5 text-center">
                                            <input type="checkbox"
                                                   name="permissions[{{ $menu->id }}][can_export]"
                                                   value="1"
                                                   {{ ($isSuper || ($perm && $perm->can_export)) ? 'checked' : '' }}
                                                   {{ $isSuper ? 'disabled' : '' }}
                                                   class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($selectedRole->role_code !== 'super_admin')
                        <div class="px-5 py-4 bg-gray-50/75 border-t border-gray-200 flex justify-end">
                            <x-button type="submit" variant="primary" size="md">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                Save Permissions for {{ $selectedRole->role_name }}
                            </x-button>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        {{-- User Role Assignment Section --}}
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">User Role Assignment</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Manage which users have Super Admin, Admin, or User access</p>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 border-b border-gray-200 text-gray-600 font-semibold">
                            <tr>
                                <th class="px-5 py-3.5">User</th>
                                <th class="px-5 py-3.5">Email</th>
                                <th class="px-5 py-3.5">Current Role</th>
                                <th class="px-5 py-3.5 text-right">Assign Role</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($users as $u)
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-5 py-3.5 font-medium text-gray-900">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-full bg-emerald-100 flex items-center justify-center font-bold text-emerald-700">
                                                {{ strtoupper(substr($u->name, 0, 1)) }}
                                            </div>
                                            <span>{{ $u->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-gray-500">{{ $u->email }}</td>
                                    <td class="px-5 py-3.5">
                                        @forelse($u->roles as $r)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold {{ $r->role_code === 'super_admin' ? 'bg-purple-100 text-purple-800' : ($r->role_code === 'admin' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700') }}">
                                                {{ $r->role_name }}
                                            </span>
                                        @empty
                                            <span class="text-gray-400">No role assigned</span>
                                        @endforelse
                                    </td>
                                    <td class="px-5 py-3.5 text-right">
                                        <button
                                            type="button"
                                            @click="targetUser = {{ json_encode($u) }}; assignModal = true"
                                            class="px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition cursor-pointer">
                                            Change Role
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($users->hasPages())
                    <div class="px-5 py-3 border-t border-gray-100">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Modal: Create New Role --}}
        <x-modal
            show="openRoleModal"
            title="Create New Role"
            subtitle="Define a custom role with customizable permissions"
            maxWidth="md">

            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                </svg>
            </x-slot:icon>

            <form action="{{ route('roles.store') }}" method="POST" class="space-y-4" novalidate>
                @csrf

                <x-form-input
                    name="role_name"
                    label="Role Name"
                    placeholder="e.g. Finance Auditor, Department Manager"
                    required />

                <x-form-textarea
                    name="description"
                    label="Description"
                    rows="3"
                    placeholder="Responsibilities and operational scope of this role..." />

                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <x-button type="button" variant="secondary" size="md" @click="openRoleModal = false">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="primary" size="md">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Create Role
                    </x-button>
                </div>
            </form>
        </x-modal>

        {{-- Modal: Assign Role to User --}}
        <x-modal
            show="assignModal"
            title="Assign User Role"
            subtitle="Update user access level and authorization"
            maxWidth="md">

            <x-slot:icon>
                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                </svg>
            </x-slot:icon>

            <form action="{{ route('roles.assign-user') }}" method="POST" class="space-y-4" novalidate>
                @csrf
                <input type="hidden" name="user_id" :value="targetUser.id">

                <div class="p-3.5 rounded-xl bg-gray-50 border border-gray-100">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider block mb-1">Target User</span>
                    <p class="text-sm font-bold text-gray-900" x-text="targetUser.name"></p>
                    <p class="text-xs text-gray-500" x-text="targetUser.email"></p>
                </div>

                <x-form-select
                    name="role_id"
                    label="Assigned Role"
                    :options="$roleOptions"
                    placeholder="Select role..."
                    searchPlaceholder="Search role..."
                    required />

                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <x-button type="button" variant="secondary" size="md" @click="assignModal = false">
                        Cancel
                    </x-button>
                    <x-button type="submit" variant="primary" size="md">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Save Assignment
                    </x-button>
                </div>
            </form>
        </x-modal>

    </div>
</x-layouts.app>
