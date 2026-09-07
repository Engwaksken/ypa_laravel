@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0">Preview: {{ $template->template_name }}</h4>
            <small class="text-muted">{{ $contract ? $contract->contract_number : 'Template preview' }}</small>
        </div>
        <a href="{{ route('contract-templates.show', $template) }}" class="btn btn-outline-dark">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            {!! $renderedHtml !!}
        </div>
    </div>
</div>
@endsection
