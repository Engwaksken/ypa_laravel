@extends('layouts.app')

@section('title', 'Member Details')

@section('content')
@php
    $permission = app(\App\Services\PermissionService::class);
    $canEdit = $permission->can('members_edit');
    $canDelete = $permission->can('members_delete');
    $m = $member;
    $statusBadge = match($m->membership_status) {
        'Active' => 'success',
        'Pending' => 'warning',
        'Suspended' => 'danger',
        'Expired', 'Terminated' => 'dark',
        default => 'secondary',
    };
    $statusAccent = match($m->membership_status) {
        'Active' => 'success',
        'Pending' => 'warning',
        'Suspended', 'Expired', 'Terminated' => 'danger',
        default => 'muted',
    };
    $kin = $m->nextOfKin->first();
    $sections = [
        ['icon' => 'fa-user', 'title' => 'Personal Information', 'rows' => [
            'First Name' => $m->first_name,
            'Last Name' => $m->last_name,
            'Other Name' => $m->other_name,
            'Date of Birth' => $m->date_of_birth ? $m->date_of_birth->format('d M Y') : null,
            'Sex' => $m->sex,
            'Nationality' => $m->nationality,
            'NIN' => $m->nin,
            'TIN Number' => $m->tin_number,
            'Marital Status' => $m->marital_status,
            'Children' => $m->children_count ?? 0,
        ]],
        ['icon' => 'fa-location-dot', 'title' => 'Address & Location', 'rows' => [
            'Address' => $m->address,
            'Region' => $m->region,
            'District of Residence' => $m->district_residence,
            'Home District' => $m->district,
        ]],
        ['icon' => 'fa-briefcase', 'title' => 'Employment & Source', 'rows' => [
            'Employment' => $m->employment_status,
            'Source' => $m->source,
            'Source Station' => $m->source_station,
            'Source (Other)' => $m->source_other,
        ]],
        ['icon' => 'fa-phone', 'title' => 'Contact Information', 'rows' => [
            'Email' => $m->email,
            'Telephone 1' => $m->telephone1,
            'Telephone 2' => $m->telephone2,
            'Mother' => trim(($m->mother_name ?? '') . ($m->mother_phone ? ' (' . $m->mother_phone . ')' : '')),
            'Father' => trim(($m->father_name ?? '') . ($m->father_phone ? ' (' . $m->father_phone . ')' : '')),
        ]],
        ['icon' => 'fa-building-columns', 'title' => 'Bank Details', 'rows' => [
            'Account Type' => $m->account_type,
            'Account Number' => $m->bank_account,
            'Account Name' => $m->bank_account_name,
            'Bank Name' => $m->bank_name,
            'Bank Branch' => $m->bank_branch,
        ]],
    ];
    if ($kin) {
        $sections[] = ['icon' => 'fa-user-group', 'title' => 'Next of Kin', 'rows' => [
            'Name' => $kin->full_name,
            'Relationship' => $kin->relationship,
            'Phone' => $kin->phone,
            'Email' => $kin->email,
        ]];
    }
    if ($m->bankDetail) {
        $sections[] = ['icon' => 'fa-building-columns', 'title' => 'Bank Details (Linked)', 'rows' => [
            'Account Number' => $m->bankDetail->bank_account,
            'Account Name' => $m->bankDetail->account_name,
            'Bank Name' => $m->bankDetail->bank_name,
            'Bank Branch' => $m->bankDetail->bank_branch,
        ]];
    }
    $sections[] = ['icon' => 'fa-clock', 'title' => 'Record Info', 'rows' => [
        'Created' => $m->created_at ? $m->created_at->format('d M Y H:i') : null,
        'Updated' => $m->updated_at ? $m->updated_at->format('d M Y H:i') : null,
    ]];
@endphp

<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">{{ $m->full_name }}</h1>
            <div class="dash-date">
                {{ $m->membership_id }}
                <span class="badge bg-{{ $statusBadge }} ms-2">{{ $m->membership_status ?? '-' }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('members.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            @if($canEdit && isset($memberRecord))
                <button type="button" class="btn btn-primary"
                    data-url="{{ route('members.update', $m) }}"
                    data-record='@json($memberRecord)'
                    onclick="openMemberEdit(this)">
                    <i class="fas fa-edit"></i> Edit Member
                </button>
            @endif
            @if($canDelete)
                <form action="{{ route('members.destroy', $m) }}" method="POST" class="ypa-confirm-delete" data-confirm-title="Delete member?" data-confirm-message="Delete {{ $m->full_name }} ({{ $m->membership_id }})? This cannot be undone.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash"></i> Delete</button>
                </form>
            @endif
        </div>
    </div>

    <div class="dash-grid d-grid-4 mb-4">
        <div class="dash-card accent-{{ $statusAccent }}">
            <div class="dash-card-top">
                <span class="dash-card-title">Membership Status</span>
                <span class="dash-card-icon"><i class="fas fa-id-card"></i></span>
            </div>
            <div class="dash-card-value">{{ $m->membership_status ?? '-' }}</div>
        </div>
        <div class="dash-card accent-primary">
            <div class="dash-card-top">
                <span class="dash-card-title">Branch</span>
                <span class="dash-card-icon"><i class="fas fa-building"></i></span>
            </div>
            <div class="dash-card-value">{{ $m->branch->name ?? '-' }}</div>
        </div>
        <div class="dash-card accent-info">
            <div class="dash-card-top">
                <span class="dash-card-title">Mobilizer</span>
                <span class="dash-card-icon"><i class="fas fa-users-cog"></i></span>
            </div>
            <div class="dash-card-value">{{ $m->mobilizer->full_name ?? '-' }}</div>
        </div>
        <div class="dash-card accent-purple">
            <div class="dash-card-top">
                <span class="dash-card-title">Account Type</span>
                <span class="dash-card-icon"><i class="fas fa-wallet"></i></span>
            </div>
            <div class="dash-card-value">{{ $m->account_type ?? '-' }}</div>
        </div>
    </div>

    <div class="row g-4">
        @foreach($sections as $section)
            <div class="col-lg-6">
                <div class="dash-panel h-100">
                    <div class="dash-panel-head">
                        <span><i class="fas {{ $section['icon'] }}"></i> {{ $section['title'] }}</span>
                    </div>
                    <div class="dash-panel-body p-0">
                        <table class="table table-sm align-middle mb-0">
                            <tbody>
                                @foreach($section['rows'] as $label => $value)
                                    <tr>
                                        <th class="text-muted fw-semibold ps-3" style="width: 40%;">{{ $label }}</th>
                                        <td class="pe-3">{{ filled($value) ? $value : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>

@if($canEdit && isset($memberRecord, $branches, $mobilizers))
    @include('members._modal_form')
@endif
@endsection
