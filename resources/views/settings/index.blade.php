@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Settings</h1>
            <div class="dash-date">Site identity and branding</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-body">
            <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <ul class="nav nav-tabs ypa-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#settings-general" type="button" role="tab">General</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#settings-branding" type="button" role="tab">Branding</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="settings-general" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Site Name</label>
                                <input type="text" name="site_name" value="{{ old('site_name', $siteName) }}" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Backup Email</label>
                                <input type="email" name="backup_email" value="{{ old('backup_email', $backupEmail) }}" class="form-control" placeholder="backup@example.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address Details</label>
                                <textarea name="address_details" rows="3" class="form-control" placeholder="Physical address shown on documents">{{ old('address_details', $addressDetails) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="settings-branding" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Site Logo</label>
                                <div class="mb-2">
                                    <img src="{{ asset($siteLogo !== '' ? $siteLogo : 'images/logo.png') }}" alt="Site logo" style="max-width:150px;max-height:70px;object-fit:contain;background:#f1f5f9;border-radius:8px;padding:6px;">
                                </div>
                                <input type="file" name="site_logo" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Favicon</label>
                                <div class="mb-2">
                                    <img src="{{ asset($siteFavicon !== '' ? $siteFavicon : 'images/favicon.png') }}" alt="Favicon" style="max-width:48px;max-height:48px;object-fit:contain;background:#f1f5f9;border-radius:8px;padding:6px;">
                                </div>
                                <input type="file" name="site_favicon" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Settings</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
