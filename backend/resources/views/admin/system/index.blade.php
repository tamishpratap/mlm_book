@extends('admin.layouts.master')
@section('title', 'System Tools & Monitoring Center')
@section('page-subtitle', 'Monitor system health, queue status, failed jobs, and execute safe artisan tools.')

@section('content')
<!-- Health Status & Metric Cards Grid -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Database Connection" 
            :value="$dbConnected ? 'Online' : 'Offline'" 
            icon="database" 
            :variant="$dbConnected ? 'success' : 'danger'" 
            subtext="Primary MySQL PDO" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Pending Queue Jobs" 
            :value="$pendingJobsCount" 
            icon="layers" 
            variant="primary" 
            subtext="Queued Worker Jobs" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Failed Jobs" 
            :value="$failedJobsCount" 
            icon="alert-octagon" 
            :variant="$failedJobsCount > 0 ? 'danger' : 'success'" 
            subtext="Worker Exception Queue" 
        />
    </div>
    <div class="col-xl-3 col-md-6 col-sm-12">
        <x-admin.stat-card 
            title="Laravel Log File Size" 
            :value="$logSizeFormatted" 
            icon="file-text" 
            variant="info" 
            subtext="storage/logs/laravel.log" 
        />
    </div>
</div>

<!-- Safe Artisan Optimization Actions Bar -->
<x-admin.card title="Safe Artisan Optimization Tools" class="mb-4">
    <div class="d-flex flex-wrap gap-2">
        <form action="{{ route('admin.system.artisan') }}" method="POST">
            @csrf
            <input type="hidden" name="command" value="optimize:clear">
            <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-flash me-1"></i> Run optimize:clear</button>
        </form>
        <form action="{{ route('admin.system.artisan') }}" method="POST">
            @csrf
            <input type="hidden" name="command" value="cache:clear">
            <button type="submit" class="btn btn-sm btn-outline-secondary">Clear Cache</button>
        </form>
        <form action="{{ route('admin.system.artisan') }}" method="POST">
            @csrf
            <input type="hidden" name="command" value="config:clear">
            <button type="submit" class="btn btn-sm btn-outline-secondary">Clear Config</button>
        </form>
        <form action="{{ route('admin.system.artisan') }}" method="POST">
            @csrf
            <input type="hidden" name="command" value="route:clear">
            <button type="submit" class="btn btn-sm btn-outline-secondary">Clear Routes</button>
        </form>
        <form action="{{ route('admin.system.artisan') }}" method="POST">
            @csrf
            <input type="hidden" name="command" value="view:clear">
            <button type="submit" class="btn btn-sm btn-outline-secondary">Clear Views</button>
        </form>
        <a href="{{ route('admin.system.logs') }}" class="btn btn-sm btn-info text-white ms-auto">
            <i class="fa fa-terminal me-1"></i> Open Log Viewer
        </a>
    </div>
</x-admin.card>

<!-- Failed Jobs Monitor Table -->
<x-admin.card title="Failed Jobs Monitor Queue">
    <x-admin.table :headers="['Job ID', 'Queue', 'Exception Snippet', 'Failed At', 'Actions']" :empty="$failedJobs->isEmpty()" emptyMessage="No failed worker jobs. System queues are running clean.">
        @foreach($failedJobs as $job)
            <tr>
                <td><span class="fw-bold text-primary">#{{ $job->id }}</span></td>
                <td><span class="badge bg-light-primary text-primary">{{ $job->queue }}</span></td>
                <td>
                    <div class="text-truncate text-danger small font-monospace" style="max-width: 320px;" title="{{ $job->exception }}">
                        {{ $job->exception }}
                    </div>
                </td>
                <td><small class="text-muted">{{ $job->failed_at }}</small></td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <form action="{{ route('admin.system.failed-jobs.retry', $job->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-icon btn-outline-success" title="Retry Failed Job">
                                <i data-feather="refresh-cw"></i>
                            </button>
                        </form>

                        <form action="{{ route('admin.system.failed-jobs.delete', $job->id) }}" method="POST" onsubmit="return confirm('Delete failed job record #{{ $job->id }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Delete Record">
                                <i data-feather="trash-2"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.table>
    
    <x-admin.pagination :paginator="$failedJobs" />
</x-admin.card>

<!-- System & Environment Information Grid -->
<x-admin.card title="Environment & Server Diagnostics" class="mt-4">
    <div class="row g-3">
        @foreach($envInfo as $key => $val)
            <div class="col-md-3 col-sm-6">
                <div class="p-3 bg-light rounded">
                    <small class="text-muted text-uppercase d-block" style="font-size: 11px;">{{ str_replace('_', ' ', $key) }}</small>
                    <strong class="text-dark">{{ $val }}</strong>
                </div>
            </div>
        @endforeach
    </div>
</x-admin.card>
@endsection
