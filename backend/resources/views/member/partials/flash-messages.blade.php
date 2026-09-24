@if (session('success'))
    <div class="member-alert member-alert--success" role="status">
        <i data-lucide="circle-check" aria-hidden="true"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="member-alert member-alert--error" role="alert">
        <i data-lucide="circle-alert" aria-hidden="true"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

@if ($errors->any())
    <div class="member-alert member-alert--error" role="alert">
        <i data-lucide="circle-alert" aria-hidden="true"></i>
        <span>Please correct the highlighted fields and try again.</span>
    </div>
@endif
