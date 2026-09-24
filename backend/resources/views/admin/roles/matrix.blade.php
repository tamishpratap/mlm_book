@extends('admin.layouts.master')
@section('title', 'Role Permission Matrix')
@section('page-subtitle', 'Interactive matrix to review and bulk assign permissions across all admin roles.')

@section('content')
<x-admin.card title="Interactive Permission Matrix">
    <x-slot name="headerAction">
        <a href="{{ route('admin.roles.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back to Roles
        </a>
    </x-slot>

    <form action="{{ route('admin.roles.matrix.update') }}" method="POST">
        @csrf

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle text-center">
                <thead class="bg-primary text-white">
                    <tr>
                        <th class="text-start" style="min-width: 220px;">Module / Permission Rule</th>
                        @foreach($roles as $role)
                            <th style="min-width: 140px;">
                                <div class="fw-bold">{{ $role->name }}</div>
                                <small class="text-white-50" style="font-size: 10px;">{{ $role->slug }}</small>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissions as $group => $groupPerms)
                        <tr class="bg-light">
                            <td colspan="{{ count($roles) + 1 }}" class="text-start fw-bold text-primary text-uppercase" style="font-size: 11px;">
                                <i class="fa fa-folder-open me-1"></i> Module Group: {{ ucfirst(str_replace('_', ' ', $group)) }}
                            </td>
                        </tr>
                        @foreach($groupPerms as $perm)
                            <tr>
                                <td class="text-start">
                                    <div class="fw-bold text-dark small">{{ $perm->name }}</div>
                                    <code style="font-size: 10px;">{{ $perm->slug }}</code>
                                </td>
                                @foreach($roles as $role)
                                    <td>
                                        <input class="form-check-input" type="checkbox" name="matrix[{{ $role->id }}][{{ $perm->id }}]" value="1" {{ $role->hasPermission($perm->slug) ? 'checked' : '' }} {{ $role->is_system && $role->slug === 'super-admin' ? 'disabled checked' : '' }}>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-3 border-top text-end">
            <x-admin.button type="submit" variant="primary" icon="save">Save Permission Matrix</x-admin.button>
        </div>
    </form>
</x-admin.card>
@endsection
