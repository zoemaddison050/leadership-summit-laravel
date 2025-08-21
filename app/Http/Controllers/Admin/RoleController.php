<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('name')->paginate(20);
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $availablePermissions = [
            'manage_events' => 'Manage Events',
            'manage_users' => 'Manage Users',
            'manage_speakers' => 'Manage Speakers',
            'manage_sessions' => 'Manage Sessions',
            'manage_pages' => 'Manage Pages',
            'manage_media' => 'Manage Media',
            'manage_payments' => 'Manage Payments',
            'manage_registrations' => 'Manage Registrations',
            'manage_tickets' => 'Manage Tickets',
            'manage_roles' => 'Manage Roles',
            'manage_settings' => 'Manage Settings',
            'view_reports' => 'View Reports'
        ];

        return view('admin.roles.create', compact('availablePermissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'permissions' => 'array',
            'permissions.*' => 'string'
        ]);

        Role::create([
            'name' => $request->name,
            'permissions' => $request->permissions ?? []
        ]);

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function show(Role $role)
    {
        $role->loadCount('users');
        return view('admin.roles.show', compact('role'));
    }

    public function edit(Role $role)
    {
        $availablePermissions = [
            'manage_events' => 'Manage Events',
            'manage_users' => 'Manage Users',
            'manage_speakers' => 'Manage Speakers',
            'manage_sessions' => 'Manage Sessions',
            'manage_pages' => 'Manage Pages',
            'manage_media' => 'Manage Media',
            'manage_payments' => 'Manage Payments',
            'manage_registrations' => 'Manage Registrations',
            'manage_tickets' => 'Manage Tickets',
            'manage_roles' => 'Manage Roles',
            'manage_settings' => 'Manage Settings',
            'view_reports' => 'View Reports'
        ];

        return view('admin.roles.edit', compact('role', 'availablePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array',
            'permissions.*' => 'string'
        ]);

        $role->update([
            'name' => $request->name,
            'permissions' => $request->permissions ?? []
        ]);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        // Prevent deleting roles that have users
        if ($role->users()->count() > 0) {
            return redirect()->back()->with('error', 'Cannot delete role that has assigned users.');
        }

        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }
}
