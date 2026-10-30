@extends('service-requests.layout')

@section('title', 'Service Request Details')

@section('content')
    <a class="back-link" href="{{ route('service-requests.index') }}">← Back to Service Request Management</a>

    <div class="page-heading page-heading--form">
        <div>
            <p class="eyebrow">Request record</p>
            <h1>Service Request #{{ $serviceRequest->id }}</h1>
            <p class="intro">Review the submitted information and its current workflow status.</p>
        </div>
        <span class="status status--large status--{{ strtolower(str_replace(' ', '-', $serviceRequest->status)) }}">{{ $serviceRequest->status }}</span>
    </div>

    <section class="panel detail-panel" aria-labelledby="detail-heading">
        <div class="panel__top"><h2 id="detail-heading">Request Details</h2></div>
        <dl class="request-details">
            <div><dt>ID</dt><dd>#{{ $serviceRequest->id }}</dd></div>
            <div><dt>Resident ID</dt><dd>#{{ $serviceRequest->residentId }}</dd></div>
            <div><dt>Service Type</dt><dd>{{ $serviceRequest->serviceType }}</dd></div>
            <div><dt>Date Requested</dt><dd>{{ $serviceRequest->dateRequested }}</dd></div>
            <div class="detail-full"><dt>Description</dt><dd>{{ $serviceRequest->description }}</dd></div>
            <div><dt>Status</dt><dd><span class="status status--{{ strtolower(str_replace(' ', '-', $serviceRequest->status)) }}">{{ $serviceRequest->status }}</span></dd></div>
        </dl>
        <div class="detail-actions">
            <h3>Available Actions</h3>
            @include('service-requests.actions')
        </div>
    </section>
@endsection
