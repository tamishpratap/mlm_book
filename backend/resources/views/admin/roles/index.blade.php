@extends('admin.layouts.master')
@section('title', 'Roles & Permissions (RBAC)')
@section('page-subtitle', 'Manage enterprise admin roles, permission matrix, and user access control.')

@section('content')
<!-- Metric Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total Roles" 
            :value="$totalRoles" 
            icon="shield" 
            variant="primary" 
            subtext="Configured System Roles" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Custom Roles" 
            :value="$customRoles" 
            icon="user-check" 
            variant="info" 
            subtext="Custom Defined Roles" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Assigned Admins" 
            :value="$totalUsersAssigned" 
            icon="users" 
            variant="success" 
            subtext="Users with Active Roles" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Total Permissions" 
            :value="$totalPermissions" 
            icon="key" 
            variant="warning" 
            subtext="Granular Module Rules" 
        />
    </div>
</div>

<!-- Roles Directory Card -->
<x-admin.card title="Admin Roles Directory">
    <x-slot name="headerAction">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.roles.matrix') }}" class="btn btn-sm btn-outline-primary">
                <i class="fa fa-th me-1"></i> Permission Matrix
            </a>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-sm btn-primary">
                <i class="fa fa-plus me-1"></i> Create Custom Role
            </a>
        </div>
    </x-slot>

    <!-- Search Form -->
    <form action="{{ route('admin.roles.index') }}" method="GET" class="row g-3 mb-4">
        <div class="col-md-6">
            <x-admin.form.group label="Search Roles" for="q">
                <x-admin.form.input name="q" :value="$search" placeholder="Search Role Name, Slug, Description..." icon="search" />
            </x-admin.form.group>
        </div>
        <div class="col-md-3 d-flex align-items-end mb-3">
            <x-admin.button type="submit" variant="primary" icon="filter">Filter</x-admin.button>
        </div>
    </form>

    <!-- Roles Table -->
    <x-admin.table :headers="['Role ID', 'Role Name', 'Slug', 'Assigned Users', 'Permissions Count', 'System Protected', 'Actions']" :empty="$roles->isEmpty()" emptyMessage="No admin roles found.">
        @foreach($roles as $role)
            <tr>
                <td><span class="fw-bold text-primary">#{{ $role->id }}</span></td>
                <td>
                    <div class="fw-bold text-dark">{{ $role->name }}</div>
                    <small class="text-muted">{{ $role->description ?: 'No description provided.' }}</small>
                </td>
                <td><code>{{ $role->slug }}</code></td>
                <td>
                    <span class="badge bg-light-primary text-primary fw-bold"><i class="fa fa-users me-1"></i> {{ count($role->users) }} Admins</span>
                </td>
                <td>
                    <span class="badge bg-light-info text-info fw-bold">{{ count($role->permissions) }} Permissions</span>
                </td>
                <td>
                    @if($role->is_system)
                        <span class="badge bg-warning text-dark"><i class="fa fa-lock me-1"></i> System Core</span>
                    @else
                        <span class="badge bg-light-secondary text-secondary">Custom Role</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Edit Role Permissions">
                            <i data-feather="edit-2"></i>
                        </a>

                        @if(!$role->is_system)
                            <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete Role {{ $role->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Role">
                                    <i data-feather="trash-2"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>
</x-admin.card>

<!-- Admin User Role Assignment Card -->
<x-admin.card title="Admin User Role Assignment" class="mt-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Admin Name</th>
                    <th>Email</th>
                    <th>Assigned Role</th>
                    <th>Update Role</th>
                </tr>
            </thead>
            <tbody>
                @foreach($adminUsers as $user)
                    <tr>
                        <td><div class="fw-bold text-dark">{{ $user->name }}</div></td>
                        <td><code>{{ $user->email }}</code></td>
                        <td>
                            @if($user->roles->isEmpty())
                                <span class="badge bg-secondary">No Role Assigned</span>
                            @else
                                @foreach($user->roles as $r)
                                    <span class="badge bg-primary me-1">{{ $r->name }}</span>
                                @endforeach
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('admin.roles.assign-user') }}" method="POST" class="d-flex align-items-center gap-2">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $user->id }}">
                                <select name="role_id" class="form-select form-select-sm" style="width: 180px;" required>
                                    <option value="">Select Role</option>
                                    @foreach($roles as $r)
                                        <option value="{{ $r->id }}" {{ $user->roles->contains('id', $r->id) ? 'selected' : '' }}>{{ $r->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-success">Assign</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin.card>
@endsection
