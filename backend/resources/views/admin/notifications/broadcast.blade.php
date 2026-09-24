@extends('admin.layouts.master')
@section('title', 'Send Broadcast Announcement')
@section('page-subtitle', 'Dispatch system notifications to target member segments.')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <x-admin.card title="Broadcast Announcement Form">
            <form action="{{ route('admin.notifications.broadcast.send') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <x-admin.form.group label="Target Audience Segment" for="audience">
                        <x-admin.form.select name="audience" placeholder="Select Target Audience" :options="[
                            'all' => 'All Platform Members',
                            'active' => 'Active Members Only',
                            'business_owners' => 'Business Page Owners',
                            'sellers' => 'Marketplace Sellers'
                        ]" required />
                    </x-admin.form.group>
                </div>

                <div class="mb-3">
                    <x-admin.form.group label="Announcement Title" for="title">
                        <x-admin.form.input name="title" placeholder="e.g. Platform Maintenance Notice / New Feature Release" required />
                    </x-admin.form.group>
                </div>

                <div class="mb-4">
                    <x-admin.form.group label="Announcement Body Message" for="message">
                        <x-admin.form.textarea name="message" placeholder="Type the message content to be delivered to members..." rows="5" required />
                    </x-admin.form.group>
                </div>

                <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i> Cancel
                    </a>
                    <x-admin.button type="submit" variant="primary" icon="send" onclick="return confirm('Dispatch broadcast announcement to selected audience segment?')">
                        Dispatch Broadcast Announcement
                    </x-admin.button>
                </div>
            </form>
        </x-admin.card>
    </div>

    <!-- Right Column: Broadcast Policy Info -->
    <div class="col-lg-4">
        <x-admin.card title="Broadcast Delivery Rules">
            <ul class="list-group list-group-flush small">
                <li class="list-group-item px-0">
                    <i class="fa fa-check-circle text-success me-2"></i> Broadcast announcements are delivered as database notifications to members' in-app notification inbox.
                </li>
                <li class="list-group-item px-0">
                    <i class="fa fa-check-circle text-success me-2"></i> All broadcast dispatches are logged and trackable in the Notification Queue.
                </li>
                <li class="list-group-item px-0">
                    <i class="fa fa-info-circle text-info me-2"></i> Target segment filtering prevents unwanted mass spamming.
                </li>
            </ul>
        </x-admin.card>
    </div>
</div>
@endsection
