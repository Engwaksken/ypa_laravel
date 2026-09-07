@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-0">New Receivable</h4>
            <small class="text-muted">Record an income receivable</small>
        </div>
        <a href="{{ route('receivables.index') }}" class="btn btn-outline-dark">Back</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('receivables.store') }}">
            @include('receivables._form')
        </form>
    </div></div>
</div>
@endsection
