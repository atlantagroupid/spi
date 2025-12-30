@extends('layouts.app')

@section('title', 'Kelola Lokasi Gudang')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Kelola Lokasi Gudang</h1>
</div>

<div class="card shadow border-0">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="location-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="gudang-tab" data-bs-toggle="tab" href="#gudang-content" role="tab">Gudang</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="gate-tab" data-bs-toggle="tab" href="#gate-content" role="tab">Gate</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="block-tab" data-bs-toggle="tab" href="#block-content" role="tab">Block</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="location-tabs-content">
            {{-- Gudang Tab --}}
            <div class="tab-pane fade show active" id="gudang-content" role="tabpanel">
                @include('settings.partials.locations_gudang')
            </div>
            {{-- Gate Tab --}}
            <div class="tab-pane fade" id="gate-content" role="tabpanel">
                @include('settings.partials.locations_gate')
            </div>
            {{-- Block Tab --}}
            <div class="tab-pane fade" id="block-content" role="tabpanel">
                @include('settings.partials.locations_block')
            </div>
        </div>
    </div>
</div>
@endsection
