@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h4 class="mb-3">Edit Contract Template</h4>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('contract-templates.update', $template) }}">
                @method('PUT')
                @include('contract_templates._form')
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Update Template</button>
                    <a href="{{ route('contract-templates.show', $template) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
