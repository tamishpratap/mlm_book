@extends('admin.layouts.master')
@section('title', 'Product Inspection: ' . $product->title)
@section('page-subtitle', 'Read-only marketplace product inspection, media gallery, seller info & report log.')

@section('content')
<div class="row g-4 mb-4">
    <!-- Left Column: Product Details & Gallery -->
    <div class="col-lg-8">
        <!-- Product Main Card -->
        <x-admin.card>
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <h4 class="fw-bold text-dark mb-0">{{ $product->title }}</h4>
                        <span class="badge bg-light-primary text-primary">{{ $product->category?->name ?? 'Uncategorized' }}</span>
                        @if($product->is_featured)
                            <span class="badge bg-warning text-dark"><i class="fa fa-star me-1"></i> Featured</span>
                        @endif
                    </div>
                    <small class="text-muted">Listed by <strong>{{ $product->member?->name }}</strong> ({{ $product->member?->user_id }}) • Listed on {{ $product->created_at?->format('F d, Y \a\t H:i') }}</small>
                </div>

                <div class="d-flex gap-2">
                    <form action="{{ route('admin.marketplace.toggle-featured', $product) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm {{ $product->is_featured ? 'btn-warning' : 'btn-outline-warning' }}">
                            <i class="fa fa-star me-1"></i> {{ $product->is_featured ? 'Unfeature' : 'Feature' }}
                        </button>
                    </form>

                    <form action="{{ route('admin.marketplace.status', $product) }}" method="POST" class="d-inline">
                        @csrf
                        @if($product->status === 'available')
                            <input type="hidden" name="status" value="hidden">
                            <x-admin.button type="submit" variant="secondary" icon="slash">Hide Product</x-admin.button>
                        @else
                            <input type="hidden" name="status" value="available">
                            <x-admin.button type="submit" variant="success" icon="check">Mark Available</x-admin.button>
                        @endif
                    </form>

                    <a href="{{ route('admin.marketplace.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>

            <!-- Price & Key Details Row -->
            <div class="row g-3 mb-4 p-3 bg-light rounded align-items-center">
                <div class="col-md-3 text-center border-end">
                    <small class="text-muted text-uppercase d-block" style="font-size: 11px;">Listing Price</small>
                    <h3 class="fw-bold text-success mb-0">${{ number_format($product->price, 2) }}</h3>
                    @if($product->is_negotiable)
                        <small class="badge bg-light-info text-info" style="font-size: 10px;">Negotiable</small>
                    @endif
                </div>
                <div class="col-md-3 text-center border-end">
                    <small class="text-muted text-uppercase d-block" style="font-size: 11px;">Condition</small>
                    <h5 class="fw-bold text-dark mb-0">{{ ucfirst(str_replace('_', ' ', $product->condition)) }}</h5>
                </div>
                <div class="col-md-3 text-center border-end">
                    <small class="text-muted text-uppercase d-block" style="font-size: 11px;">Stock Quantity</small>
                    <h5 class="fw-bold text-dark mb-0">{{ $product->quantity }}</h5>
                </div>
                <div class="col-md-3 text-center">
                    <small class="text-muted text-uppercase d-block" style="font-size: 11px;">Status</small>
                    <span class="badge {{ $product->status === 'available' ? 'bg-success' : 'bg-secondary' }} font-weight-bold">
                        {{ ucfirst($product->status) }}
                    </span>
                </div>
            </div>

            <!-- Gallery Media Preview Grid -->
            <h6 class="fw-bold text-primary mb-3">Product Media Gallery ({{ count($product->media) }})</h6>
            @if($product->media->isEmpty())
                <p class="text-muted small">No product photos uploaded.</p>
            @else
                <div class="row g-2 mb-4">
                    @foreach($product->media as $m)
                        @php
                            $mUrl = str_starts_with($m->media_path, 'http') ? $m->media_path : asset($m->media_path);
                        @endphp
                        <div class="col-md-4 col-6">
                            <div class="border rounded overflow-hidden position-relative bg-dark" style="height: 160px;">
                                <img src="{{ $mUrl }}" alt="Product Image" class="w-100 h-100" style="object-fit: cover;">
                                @if($m->is_featured)
                                    <span class="badge bg-warning position-absolute top-0 start-0 m-2">Main Image</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Tabbed Details Section -->
            <x-admin.tabs id="productTabs" :tabs="[
                'overview' => 'Description & Specs',
                'seller' => 'Seller Info',
                'reports' => 'Reports Queue (' . $product->reports_count . ')'
            ]">
                <!-- Overview Pane -->
                <div class="tab-pane fade show active" id="overview-pane" role="tabpanel" aria-labelledby="overview-tab">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-3">Specifications</h6>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted fw-bold" style="width: 140px;">Title:</td>
                                    <td class="fw-bold text-dark">{{ $product->title }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-bold">Brand:</td>
                                    <td>{{ $product->brand ?: 'Unspecified' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-bold">Category:</td>
                                    <td>{{ $product->category?->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-bold">Sub Category:</td>
                                    <td>{{ $product->subCategory?->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-bold">Location:</td>
                                    <td>{{ $product->location ?: 'Not specified' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-bold">Tags:</td>
                                    <td>{{ $product->tags ?: 'None' }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-2">Description</h6>
                            <p class="text-muted small bg-light p-3 rounded mb-0" style="white-space: pre-line;">
                                {{ $product->description }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Seller Pane -->
                <div class="tab-pane fade" id="seller-pane" role="tabpanel" aria-labelledby="seller-tab">
                    <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-light rounded">
                        <img class="rounded-circle" src="{{ $product->member?->avatar_url }}" style="width: 54px; height: 54px; object-fit: cover;">
                        <div>
                            <h5 class="fw-bold text-dark mb-0">{{ $product->member?->name ?? 'Deleted Member' }}</h5>
                            <code class="text-primary">{{ $product->member?->user_id }}</code>
                            <p class="text-muted small mb-0 mt-1">{{ $product->member?->email }} • {{ $product->member?->phone ?? 'Phone not provided' }}</p>
                        </div>
                        <div class="ms-auto">
                            @if($product->member)
                                <a href="{{ route('admin.members.show', $product->member) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-user me-1"></i> View Full Member Profile
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Reports Pane -->
                <div class="tab-pane fade" id="reports-pane" role="tabpanel" aria-labelledby="reports-tab">
                    <x-admin.table :headers="['Report ID', 'Reporter', 'Reason', 'Notes', 'Date']" :empty="$product->reports->isEmpty()" emptyMessage="No reports filed for this product listing.">
                        @foreach($product->reports as $report)
                            <tr>
                                <td>#{{ $report->id }}</td>
                                <td><small class="fw-bold text-dark">{{ $report->member?->name ?? 'Anonymous' }}</small></td>
                                <td><span class="badge bg-danger">{{ $report->reason }}</span></td>
                                <td><small class="text-muted">{{ $report->notes ?: 'N/A' }}</small></td>
                                <td><small class="text-muted">{{ $report->created_at?->format('M d, Y') }}</small></td>
                            </tr>
                        @endforeach
                    </x-admin.table>
                </div>
            </x-admin.tabs>
        </x-admin.card>
    </div>

    <!-- Right Column: Product Statistics & Moderation Card -->
    <div class="col-lg-4">
        <!-- Analytics Metric Cards -->
        <x-admin.card title="Product Analytics" class="mb-4">
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span><i class="fa fa-eye me-2 text-primary"></i> Page Views</span>
                    <strong class="text-dark">{{ number_format($product->views_count) }}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span><i class="fa fa-bookmark me-2 text-info"></i> Saved / Wishlist</span>
                    <strong class="text-dark">{{ $product->saved_products_count }}</strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <span><i class="fa fa-flag me-2 text-danger"></i> Reports Count</span>
                    <strong class="text-danger">{{ $product->reports_count }}</strong>
                </li>
            </ul>
        </x-admin.card>

        <!-- Moderation Actions Card -->
        <x-admin.card title="Moderation Actions">
            <form action="{{ route('admin.marketplace.destroy', $product) }}" method="POST" onsubmit="return confirm('Delete Product {{ $product->title }} permanently?')">
                @csrf
                @method('DELETE')
                <x-admin.button type="submit" variant="danger" icon="trash-2" class="w-100 mb-2">Delete Listing</x-admin.button>
            </form>
        </x-admin.card>
    </div>
</div>
@endsection
