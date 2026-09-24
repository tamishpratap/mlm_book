@extends('admin.layouts.master')
@section('title', 'Platform Settings & Configuration Center')
@section('page-subtitle', 'Manage global application settings, branding assets, cache, and system diagnostics.')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <x-admin.card>
            <x-admin.tabs id="settingTabs" active="general" :tabs="[
                'general' => 'General Settings',
                'branding' => 'Branding & Assets',
                'contact' => 'Contact Info',
                'mail' => 'Mail & Storage',
                'cache' => 'Cache & Maintenance',
                'seo' => 'SEO & Social',
                'system' => 'System Diagnostics'
            ]">
                <!-- General Settings Pane -->
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="group" value="general">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <x-admin.form.group label="Site Name" for="site_name">
                                    <x-admin.form.input name="site_name" :value="$settings['site_name'] ?? 'MLM Book'" placeholder="Platform Title" required />
                                </x-admin.form.group>
                            </div>

                            <div class="col-md-6">
                                <x-admin.form.group label="Default Timezone" for="timezone">
                                    <x-admin.form.select name="timezone" :selected="$settings['timezone'] ?? 'UTC'" :options="['UTC' => 'UTC', 'Asia/Kolkata' => 'Asia/Kolkata (IST)', 'America/New_York' => 'America/New_York (EST)', 'Europe/London' => 'Europe/London (GMT)']" />
                                </x-admin.form.group>
                            </div>

                            <div class="col-12">
                                <x-admin.form.group label="Site Description" for="site_description">
                                    <x-admin.form.textarea name="site_description" :value="$settings['site_description'] ?? 'Enterprise MLM Book Social & Commerce Platform.'" rows="2" />
                                </x-admin.form.group>
                            </div>

                            <div class="col-md-4">
                                <x-admin.form.group label="Date Format" for="date_format">
                                    <x-admin.form.select name="date_format" :selected="$settings['date_format'] ?? 'Y-m-d'" :options="['Y-m-d' => 'YYYY-MM-DD (2026-07-28)', 'M d, Y' => 'MMM DD, YYYY (Jul 28, 2026)', 'd/m/Y' => 'DD/MM/YYYY (28/07/2026)']" />
                                </x-admin.form.group>
                            </div>

                            <div class="col-md-4">
                                <x-admin.form.group label="Default Currency" for="currency">
                                    <x-admin.form.input name="currency" :value="$settings['currency'] ?? 'USD ($)'" placeholder="USD ($)" />
                                </x-admin.form.group>
                            </div>

                            <div class="col-md-4">
                                <x-admin.form.group label="Admin Pagination Size" for="pagination_size">
                                    <x-admin.form.select name="pagination_size" :selected="$settings['pagination_size'] ?? '15'" :options="['10' => '10 Items', '15' => '15 Items', '25' => '25 Items', '50' => '50 Items']" />
                                </x-admin.form.group>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <x-admin.button type="submit" variant="primary" icon="save">Save General Settings</x-admin.button>
                        </div>
                    </form>
                </div>

                <!-- Branding Pane -->
                <div class="tab-pane fade" id="branding" role="tabpanel" aria-labelledby="branding-tab">
                    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="group" value="branding">

                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="border rounded p-3 text-center bg-light">
                                    <h6 class="fw-bold text-dark mb-2">Main Logo</h6>
                                    <img src="{{ asset($settings['site_logo'] ?? 'logo/logo.png') }}" class="mb-3 img-fluid rounded" style="max-height: 50px;">
                                    <input type="file" name="site_logo" class="form-control form-control-sm" accept="image/*">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="border rounded p-3 text-center bg-dark text-white">
                                    <h6 class="fw-bold mb-2">Dark Theme Logo</h6>
                                    <img src="{{ asset($settings['site_dark_logo'] ?? 'logo/logo.png') }}" class="mb-3 img-fluid rounded" style="max-height: 50px;">
                                    <input type="file" name="site_dark_logo" class="form-control form-control-sm" accept="image/*">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="border rounded p-3 text-center bg-light">
                                    <h6 class="fw-bold text-dark mb-2">Favicon Icon</h6>
                                    <img src="{{ asset($settings['site_favicon'] ?? 'logo/logo.png') }}" class="mb-3 img-fluid" style="width: 32px; height: 32px;">
                                    <input type="file" name="site_favicon" class="form-control form-control-sm" accept="image/*">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <x-admin.button type="submit" variant="primary" icon="upload">Upload Branding Assets</x-admin.button>
                        </div>
                    </form>
                </div>

                <!-- Contact Info Pane -->
                <div class="tab-pane fade" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="group" value="contact">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <x-admin.form.group label="Company Name" for="company_name">
                                    <x-admin.form.input name="company_name" :value="$settings['company_name'] ?? 'MLM Book Enterprise'" />
                                </x-admin.form.group>
                            </div>

                            <div class="col-md-6">
                                <x-admin.form.group label="Support Email" for="support_email">
                                    <x-admin.form.input name="support_email" type="email" :value="$settings['support_email'] ?? 'support@mlmbook.com'" />
                                </x-admin.form.group>
                            </div>

                            <div class="col-md-6">
                                <x-admin.form.group label="Phone Number" for="phone">
                                    <x-admin.form.input name="phone" :value="$settings['phone'] ?? '+1 (800) 123-4567'" />
                                </x-admin.form.group>
                            </div>

                            <div class="col-md-6">
                                <x-admin.form.group label="Official Website" for="website">
                                    <x-admin.form.input name="website" :value="$settings['website'] ?? 'https://mlmbook.com'" />
                                </x-admin.form.group>
                            </div>

                            <div class="col-12">
                                <x-admin.form.group label="Office Address" for="address">
                                    <x-admin.form.textarea name="address" :value="$settings['address'] ?? '123 Enterprise Way, Suite 500, Tech City'" rows="2" />
                                </x-admin.form.group>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <x-admin.button type="submit" variant="primary" icon="save">Save Contact Information</x-admin.button>
                        </div>
                    </form>
                </div>

                <!-- Mail & Storage Status Pane -->
                <div class="tab-pane fade" id="mail" role="tabpanel" aria-labelledby="mail-tab">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-3">Mail Server Configuration</h6>
                            <table class="table table-sm table-borderless bg-light p-3 rounded">
                                <tr>
                                    <td class="text-muted fw-bold" style="width: 140px;">Mail Driver:</td>
                                    <td><code>{{ $systemInfo['mail_driver'] }}</code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-bold">SMTP Host:</td>
                                    <td>{{ config('mail.mailers.smtp.host', 'smtp.mailtrap.io') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-bold">SMTP Port:</td>
                                    <td>{{ config('mail.mailers.smtp.port', 2525) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-bold">From Address:</td>
                                    <td>{{ config('mail.from.address', 'noreply@mlmbook.com') }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h6 class="fw-bold text-primary mb-3">Storage Driver Status</h6>
                            <table class="table table-sm table-borderless bg-light p-3 rounded">
                                <tr>
                                    <td class="text-muted fw-bold" style="width: 140px;">Default Disk:</td>
                                    <td><x-admin.badge variant="success" :light="true">{{ config('filesystems.default', 'local') }}</x-admin.badge></td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-bold">Public Storage:</td>
                                    <td><code>storage/app/public</code></td>
                                </tr>
                                <tr>
                                    <td class="text-muted fw-bold">Symlink Status:</td>
                                    <td><span class="badge bg-success">Linked</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Cache & Maintenance Pane -->
                <div class="tab-pane fade" id="cache" role="tabpanel" aria-labelledby="cache-tab">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="p-3 border rounded">
                                <h6 class="fw-bold text-dark mb-2">Artisan Cache Management</h6>
                                <p class="text-muted small mb-3">Clear application cache, configuration cache, route cache, and compiled views safely.</p>
                                
                                <form action="{{ route('admin.settings.clear-cache') }}" method="POST" class="d-flex flex-wrap gap-2">
                                    @csrf
                                    <button type="submit" name="type" value="all" class="btn btn-primary btn-sm"><i class="fa fa-trash me-1"></i> Clear All Cache</button>
                                    <button type="submit" name="type" value="config" class="btn btn-outline-secondary btn-sm">Clear Config</button>
                                    <button type="submit" name="type" value="route" class="btn btn-outline-secondary btn-sm">Clear Routes</button>
                                    <button type="submit" name="type" value="view" class="btn btn-outline-secondary btn-sm">Clear Views</button>
                                </form>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 border rounded bg-light">
                                <h6 class="fw-bold text-dark mb-2">Platform Maintenance Mode</h6>
                                <p class="text-muted small mb-3">
                                    Current Status: 
                                    @if(app()->isDownForMaintenance())
                                        <span class="badge bg-danger">Maintenance Mode ACTIVE</span>
                                    @else
                                        <span class="badge bg-success">Platform LIVE</span>
                                    @endif
                                </p>

                                <form action="{{ route('admin.settings.maintenance') }}" method="POST">
                                    @csrf
                                    @if(app()->isDownForMaintenance())
                                        <x-admin.button type="submit" variant="success" icon="play">Disable Maintenance Mode</x-admin.button>
                                    @else
                                        <x-admin.button type="submit" variant="warning" icon="pause" onclick="return confirm('Enable maintenance mode?')">Enable Maintenance Mode</x-admin.button>
                                    @endif
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEO & Social Links Pane -->
                <div class="tab-pane fade" id="seo" role="tabpanel" aria-labelledby="seo-tab">
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        <input type="hidden" name="group" value="seo">

                        <h6 class="fw-bold text-primary mb-3">SEO Parameters</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <x-admin.form.group label="Default Meta Title" for="meta_title">
                                    <x-admin.form.input name="meta_title" :value="$settings['meta_title'] ?? 'MLM Book Enterprise Social & Commerce Platform'" />
                                </x-admin.form.group>
                            </div>
                            <div class="col-md-6">
                                <x-admin.form.group label="Meta Keywords" for="meta_keywords">
                                    <x-admin.form.input name="meta_keywords" :value="$settings['meta_keywords'] ?? 'mlm, social network, marketplace, business pages, communities'" />
                                </x-admin.form.group>
                            </div>
                            <div class="col-12">
                                <x-admin.form.group label="Default Meta Description" for="meta_description">
                                    <x-admin.form.textarea name="meta_description" :value="$settings['meta_description'] ?? 'Connect, shop, organize communities, and grow your business network on MLM Book.'" rows="2" />
                                </x-admin.form.group>
                            </div>
                        </div>

                        <h6 class="fw-bold text-primary mb-3">Official Social Links</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <x-admin.form.group label="Facebook URL" for="social_facebook">
                                    <x-admin.form.input name="social_facebook" :value="$settings['social_facebook'] ?? 'https://facebook.com'" />
                                </x-admin.form.group>
                            </div>
                            <div class="col-md-4">
                                <x-admin.form.group label="Twitter / X URL" for="social_twitter">
                                    <x-admin.form.input name="social_twitter" :value="$settings['social_twitter'] ?? 'https://x.com'" />
                                </x-admin.form.group>
                            </div>
                            <div class="col-md-4">
                                <x-admin.form.group label="Instagram URL" for="social_instagram">
                                    <x-admin.form.input name="social_instagram" :value="$settings['social_instagram'] ?? 'https://instagram.com'" />
                                </x-admin.form.group>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <x-admin.button type="submit" variant="primary" icon="save">Save SEO & Social Links</x-admin.button>
                        </div>
                    </form>
                </div>

                <!-- System Diagnostics Pane -->
                <div class="tab-pane fade" id="system" role="tabpanel" aria-labelledby="system-tab">
                    <h6 class="fw-bold text-primary mb-3">System Diagnostics & Environment</h6>
                    <div class="row g-3">
                        @foreach($systemInfo as $key => $val)
                            <div class="col-md-4 col-sm-6">
                                <div class="p-3 bg-light rounded">
                                    <small class="text-muted text-uppercase d-block" style="font-size: 11px;">{{ str_replace('_', ' ', $key) }}</small>
                                    <strong class="text-dark">{{ $val }}</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-admin.tabs>
        </x-admin.card>
    </div>
</div>
@endsection
