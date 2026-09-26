@extends('admin.layouts.master')
@section('title', 'Member Security')
@section('page-subtitle', 'Change or reset the login password for any registered platform member.')

@section('content')
<div class="row">
    <!-- Left Column: Search & Member Selection -->
    <div class="col-lg-5 mb-4">
        <x-admin.card title="Find Member">
            <form action="{{ route('admin.members.security') }}" method="GET" class="mb-3">
                <div class="input-group">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by name, @username, email, ID..." value="{{ request('q') }}">
                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="fa fa-search"></i> Search
                    </button>
                </div>
            </form>

            @php
                $searchQuery = request('q');
                $searchResults = collect();
                if ($searchQuery) {
                    $cleanUser = ltrim($searchQuery, '@');
                    $searchResults = \App\Models\Member::where('name', 'like', "%{$searchQuery}%")
                        ->orWhere('email', 'like', "%{$searchQuery}%")
                        ->orWhere('user_id', 'like', "%{$cleanUser}%")
                        ->orWhere('phone', 'like', "%{$searchQuery}%")
                        ->latest('created_at')
                        ->limit(15)
                        ->get();
                } else {
                    $searchResults = \App\Models\Member::latest('created_at')->limit(10)->get();
                }
            @endphp

            <div class="list-group list-group-flush border rounded-3 overflow-auto" style="max-height: 400px;">
                @forelse($searchResults as $m)
                    <a href="{{ route('admin.members.security', ['member_id' => $m->id, 'q' => $searchQuery]) }}" 
                       class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-2 {{ isset($selectedMember) && $selectedMember->id === $m->id ? 'active' : '' }}">
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <div class="avatar-sm rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 13px;">
                                {{ strtoupper(substr($m->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="fw-bold text-truncate" style="font-size: 13px;">{{ $m->name }}</div>
                                <div class="small text-muted text-truncate" style="font-size: 11px;">
                                    <code>{{ '@' . ltrim($m->user_id, '@') }}</code> • {{ $m->email }}
                                </div>
                            </div>
                        </div>
                        <i class="fa fa-chevron-right text-muted small"></i>
                    </a>
                @empty
                    <div class="p-3 text-center text-muted small">
                        No members found matching your search.
                    </div>
                @endforelse
            </div>
        </x-admin.card>
    </div>

    <!-- Right Column: Selected Member & Password Change Form -->
    <div class="col-lg-7">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fa fa-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <h6 class="alert-heading fw-bold mb-1"><i class="fa fa-exclamation-triangle me-1"></i> Error:</h6>
                <ul class="mb-0 ps-3 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(isset($selectedMember))
            <!-- Target Member Identity Card -->
            <x-admin.card title="Target Member Identity" class="mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-lg rounded-3 bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-3" style="width: 60px; height: 60px;">
                        {{ strtoupper(substr($selectedMember->name, 0, 1)) }}
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h5 class="fw-bold text-dark mb-0">{{ $selectedMember->name }}</h5>
                            <span class="badge bg-light text-primary border"><code>{{ '@' . ltrim($selectedMember->user_id, '@') }}</code></span>
                            @if($selectedMember->blocked_at)
                                <span class="badge bg-danger">BLOCKED</span>
                            @elseif($selectedMember->mobile_verified_at)
                                <span class="badge bg-success">VERIFIED</span>
                            @else
                                <span class="badge bg-warning text-dark">UNVERIFIED</span>
                            @endif
                        </div>
                        <div class="row g-2 text-muted small">
                            <div class="col-sm-6">
                                <strong>Email:</strong> {{ $selectedMember->email }}
                            </div>
                            <div class="col-sm-6">
                                <strong>Member ID:</strong> #{{ $selectedMember->id }}
                            </div>
                            @if($selectedMember->phone)
                                <div class="col-sm-6">
                                    <strong>Phone:</strong> {{ $selectedMember->phone }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </x-admin.card>

            <!-- Password Change Form -->
            <x-admin.card title="Change Member Password">
                <div class="alert alert-light border small text-muted mb-4">
                    <i class="fa fa-shield text-primary me-1"></i>
                    <strong>Security Protocol:</strong> Existing passwords and password hashes are never shown. Enter a new password below to overwrite and securely hash using Laravel's password mechanism.
                </div>

                <form action="{{ route('admin.members.password.update', $selectedMember) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="password" class="form-label small fw-bold">New Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" id="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" placeholder="Enter new password (min. 8 characters)" required autocomplete="new-password">
                        <div class="form-text small">Must be at least 8 characters long.</div>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label small fw-bold">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Re-enter new password" required autocomplete="new-password">
                        <div class="form-text small">Must match the new password entered above.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.members.security') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa fa-key me-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </x-admin.card>
        @else
            <div class="card shadow-sm border-0 text-center py-5">
                <div class="card-body">
                    <div class="mb-3 text-muted">
                        <i class="fa fa-lock fa-3x"></i>
                    </div>
                    <h5 class="fw-bold text-dark">No Member Selected</h5>
                    <p class="text-muted small">Please search and select a member from the directory on the left to change their account password.</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
