@extends('admin.layouts.master')
@section('title', 'Create Custom Role')
@section('page-subtitle', 'Define new custom admin role and assign module permissions.')

@section('content')
<x-admin.card title="Create Custom Role">
    <x-slot name="headerAction">
        <a href="{{ route('admin.roles.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fa fa-arrow-left me-1"></i> Back to Roles
        </a>
    </x-slot>

    <form action="{{ route('admin.roles.store') }}" method="POST">
        @csrf

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <x-admin.form.group label="Role Name" for="name">
                    <x-admin.form.input name="name" placeholder="e.g. Content Manager" required />
                </x-admin.form.group>
            </div>

            <div class="col-md-6">
                <x-admin.form.group label="Description" for="description">
                    <x-admin.form.input name="description" placeholder="Brief role summary" />
                </x-admin.form.group>
            </div>
        </div>

        <h6 class="fw-bold text-primary mb-3">Assign Module Permissions</h6>
        <div class="row g-4">
            @foreach($permissions as $group => $groupPerms)
                <div class="col-md-6">
                    <div class="border rounded p-3 bg-light">
                        <h6 class="fw-bold text-dark mb-2 text-capitalize"><i class="fa fa-folder me-1 text-primary"></i> {{ str_replace('_', ' ', $group) }}</h6>
                        <div class="d-flex flex-column gap-2">
                            @foreach($groupPerms as $perm)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $perm->id }}" id="perm_{{ $perm->id }}">
                                    <label class="form-check-label fw-bold text-dark small" for="perm_{{ $perm->id }}">
                                        {{ $perm->name }} <code style="font-size: 10px;">({{ $perm->slug }})</code>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 pt-3 border-top text-end">
            <x-admin.button type="submit" variant="primary" icon="save">Create Role</x-admin.button>
        </div>
    </form>
</x-admin.card>
@endsection
