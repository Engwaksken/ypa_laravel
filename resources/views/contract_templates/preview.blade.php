@extends('layouts.app')

@section('title', 'Preview: ' . $template->template_name)

@section('content')
<div class="dash-wrap">

    <div class="dash-head">
        <div>
            <h1 class="dash-name">Preview: {{ $template->template_name }}</h1>
            <div class="dash-date"><a href="{{ route('contract-templates.index') }}">Contract Templates</a> / {{ $contract ? $contract->contract_number : 'Template preview' }}</div>
        </div>
        <a href="{{ route('contract-templates.show', $template) }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-head">
            <span><i class="fas fa-file-contract"></i> Rendered Template</span>
        </div>
        <div class="dash-panel-body">
            {!! $renderedHtml !!}
        </div>
    </div>

</div>
@endsection
