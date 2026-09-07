@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h4 class="mb-0">Edit Receivable</h4>
            <small class="text-muted">{{ $receivable->reference_no }}</small>
        </div>
        <a href="{{ route('receivables.show', $receivable) }}" class="btn btn-outline-dark">Back</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('receivables.update', $receivable) }}">
            @include('receivables._form', ['receivable' => $receivable])
        </form>
    </div></div>
</div>
@endsection
