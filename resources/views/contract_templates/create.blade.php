@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h4 class="mb-3">New Contract Template</h4>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('contract-templates.store') }}">
                @include('contract_templates._form', ['template' => null])
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Save Template</button>
                    <a href="{{ route('contract-templates.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
