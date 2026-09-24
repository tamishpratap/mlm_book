@extends('admin.layouts.master')
@section('title', 'Laravel Logs Viewer')
@section('page-subtitle', 'Inspect recent system log entries from storage/logs/laravel.log.')

@section('content')
<x-admin.card title="System Log Viewer">
    <x-slot name="headerAction">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.system.logs.download') }}" class="btn btn-sm btn-outline-primary">
                <i class="fa fa-download me-1"></i> Download Log File
            </a>
            <form action="{{ route('admin.system.logs.clear') }}" method="POST" class="d-inline" onsubmit="return confirm('Empty storage/logs/laravel.log file?')">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="fa fa-trash me-1"></i> Clear Log File
                </button>
            </form>
            <a href="{{ route('admin.system.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </x-slot>

    <!-- Search & Level Filter -->
    <form action="{{ route('admin.system.logs') }}" method="GET" class="row g-3 mb-4">
        <div class="col-md-6">
            <x-admin.form.group label="Search Log Entries" for="q">
                <x-admin.form.input name="q" :value="$search" placeholder="Search log text or timestamp..." icon="search" />
            </x-admin.form.group>
        </div>

        <div class="col-md-3">
            <x-admin.form.group label="Severity Level" for="level">
                <x-admin.form.select name="level" :selected="$level" placeholder="All Severity Levels" :options="['ERROR' => 'ERROR', 'WARNING' => 'WARNING', 'INFO' => 'INFO', 'DEBUG' => 'DEBUG']" />
            </x-admin.form.group>
        </div>

        <div class="col-md-3 d-flex align-items-end mb-3">
            <x-admin.button type="submit" variant="primary" icon="filter" class="w-100">Filter Logs</x-admin.button>
        </div>
    </form>

    <!-- Terminal Log Stream Output Container -->
    <div class="bg-dark rounded p-3 text-white overflow-auto" style="height: 520px; font-family: monospace; font-size: 12px; line-height: 1.6;">
        @forelse($logEntries as $entry)
            <div class="border-bottom border-secondary pb-2 mb-2">
                <span class="text-secondary">[{{ $entry->date }}]</span>
                @if($entry->level === 'ERROR')
                    <span class="badge bg-danger text-uppercase me-2">{{ $entry->level }}</span>
                @elseif($entry->level === 'WARNING')
                    <span class="badge bg-warning text-dark text-uppercase me-2">{{ $entry->level }}</span>
                @else
                    <span class="badge bg-info text-uppercase me-2">{{ $entry->level }}</span>
                @endif
                <span class="text-light">{{ $entry->message }}</span>
            </div>
        @empty
            <div class="text-center text-muted py-5">
                <i class="fa fa-terminal fa-2x mb-2 d-block"></i>
                No log entries match your search criteria.
            </div>
        @endforelse
    </div>
</x-admin.card>
@endsection
