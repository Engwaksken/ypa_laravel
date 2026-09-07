@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <h4 class="mb-3">Edit Contract</h4>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('contracts.update', $contract) }}">
                @method('PUT')
                @include('contracts._form')
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Update Contract</button>
                    <a href="{{ route('contracts.show', $contract) }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
