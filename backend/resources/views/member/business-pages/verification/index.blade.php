@extends('member.layouts.app')

@section('title', 'Official Verification - ' . $businessPage->page_name)

@section('content')
<div class="biz-page">
    <header class="biz-header" style="margin-bottom: 20px;">
        <div class="biz-header__info">
            <h1><i data-lucide="badge-check" style="color: #20c875;"></i> Business Verification Portal</h1>
            <p>Verify {{ $businessPage->page_name }} to gain official trust badge, priority discovery ranking, and customer confidence.</p>
        </div>
        <div class="biz-header__actions">
            <a href="{{ route('member.business-pages.show', $businessPage) }}" class="member-button member-button--secondary">
                <i data-lucide="arrow-left"></i> Back to Profile
            </a>
        </div>
    </header>

    <div style="max-width: 850px; margin: 0 auto; width: 100%; display: flex; flex-direction: column; gap: 20px;">
        <!-- Status Card -->
        <div class="biz-info-card" style="padding: 24px; text-align: center; background: #fff;">
            @if ($status === 'verified')
                <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(32, 200, 117, 0.15); color: #20c875; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <i data-lucide="badge-check" style="width: 32px; height: 32px;"></i>
                </div>
                <h2 style="font-size: 22px; font-weight: 800; color: #1d2738; margin: 0 0 6px 0;">Official Verified Business Page</h2>
                <p style="font-size: 14px; color: #687386; margin: 0;">Congratulations! Your business page has been officially verified by the platform moderation team.</p>
            @elseif ($status === 'pending')
                <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(247, 185, 64, 0.15); color: #f7b940; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px;">
                    <i data-lucide="clock" style="width: 32px; height: 32px;"></i>
                </div>
                <h2 style="font-size: 22px; font-weight: 800; color: #1d2738; margin: 0 0 6px 0;">Verification Pending Review</h2>
                <p style="font-size: 14px; color: #687386; margin: 0;">Your submitted documents are currently undergoing review. We will notify you once verified.</p>
            @else
                <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(79, 125, 243, 0.1); color: #4f7df3; display: inline-flex; align-items: center; justify-content: justify; align-items: center; margin-bottom: 12px;">
                    <i data-lucide="shield" style="width: 32px; height: 32px;"></i>
                </div>
                <h2 style="font-size: 22px; font-weight: 800; color: #1d2738; margin: 0 0 6px 0;">Get Verified Badge</h2>
                <p style="font-size: 14px; color: #687386; margin: 0;">Submit official registration documents to verify your business identity.</p>
            @endif
        </div>

        <!-- Document Upload Form (Only if not verified & not pending) -->
        @if ($status !== 'verified' && $status !== 'pending')
            <div class="biz-info-card" style="padding: 24px;">
                <h3 class="biz-info-card__title" style="margin-bottom: 16px;">
                    <i data-lucide="file-up" style="color: #4f7df3;"></i> Submit Verification Document
                </h3>

                <form id="bizVerificationForm" method="POST" action="{{ route('member.business-pages.verification.store', $businessPage) }}" enctype="multipart/form-data">
                    @csrf
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div class="form-group">
                            <label style="font-size: 13.5px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">
                                Document Type <span style="color: red;">*</span>
                            </label>
                            <select name="document_type" class="biz-filter-select" style="width: 100%;" required>
                                <option value="business_registration">Business Registration Certificate (LLC / Inc / Private Ltd)</option>
                                <option value="gst">GST / Tax Identification Certificate</option>
                                <option value="license">Official Business License</option>
                                <option value="govt_id">Owner Government-Issued ID / Passport</option>
                                <option value="other">Other Official Document</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label style="font-size: 13.5px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">
                                Registration / Document Number (Optional)
                            </label>
                            <input type="text" name="document_number" class="biz-search-input" placeholder="e.g. REG-98472910, GSTIN12345..." maxlength="100">
                        </div>

                        <div class="form-group">
                            <label style="font-size: 13.5px; font-weight: 700; color: #1d2738; margin-bottom: 6px; display: block;">
                                Document File (PDF or Image, Max 10MB) <span style="color: red;">*</span>
                            </label>
                            <input type="file" name="document_file" accept=".pdf,.png,.jpg,.jpeg,.webp" class="biz-search-input" style="padding: 8px;" required>
                        </div>

                        <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                            <button type="submit" id="submitVerifyBtn" class="member-button member-button--primary">
                                <i data-lucide="upload"></i> Submit for Verification
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        @endif

        <!-- Submission History Matrix -->
        @if ($verifications->count() > 0)
            <div class="biz-info-card" style="padding: 20px;">
                <h3 class="biz-info-card__title" style="font-size: 15px; margin-bottom: 14px;">
                    <i data-lucide="history" style="color: #4f7df3;"></i> Verification History
                </h3>
                <div style="overflow-x: auto;">
                    <table class="biz-table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="border-bottom: 2px solid #e7ecf4; text-align: left; color: #687386;">
                                <th style="padding: 10px;">Document Type</th>
                                <th style="padding: 10px;">Document No.</th>
                                <th style="padding: 10px;">Submitted On</th>
                                <th style="padding: 10px; text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($verifications as $v)
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 10px; font-weight: 600; color: #1d2738;">
                                        {{ str_replace('_', ' ', strtoupper($v->document_type)) }}
                                    </td>
                                    <td style="padding: 10px; color: #687386;">{{ $v->document_number ?? 'N/A' }}</td>
                                    <td style="padding: 10px; color: #98a2b3;">{{ $v->created_at->format('M d, Y H:i') }}</td>
                                    <td style="padding: 10px; text-align: center;">
                                        @if ($v->status === 'verified')
                                            <span class="biz-badge biz-badge--verified" style="font-size: 10px;">Verified</span>
                                        @elseif ($v->status === 'pending')
                                            <span class="biz-badge" style="font-size: 10px; background: rgba(247, 185, 64, 0.15); color: #b7791f;">Pending</span>
                                        @else
                                            <span class="biz-badge biz-badge--unverified" style="font-size: 10px;">{{ ucfirst($v->status) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
document.getElementById('bizVerificationForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const btn = document.getElementById('submitVerifyBtn');
    btn.disabled = true;

    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        alert(data.message);
        if (data.success) {
            location.reload();
        }
    })
    .catch(err => {
        btn.disabled = false;
        alert('An error occurred during document upload.');
    });
});
</script>
@endsection
