@extends('service-requests.layout')

@section('title', 'Service Request Management')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Community services</p>
            <h1>Service Request Management</h1>
            <p class="intro">Find requests, review their details, and move work through the service process.</p>
        </div>
        <a class="button button--primary" href="{{ route('service-requests.create') }}">Submit Service Request</a>
    </div>

    <section class="panel" aria-labelledby="request-list-heading">
        <div class="panel__top">
            <div>
                <p class="eyebrow">Work queue</p>
                <h2 id="request-list-heading">Service Requests</h2>
            </div>
            <span class="count">{{ $requests->count() }} {{ $requests->count() === 1 ? 'record' : 'records' }}</span>
        </div>

        <form class="search-form request-search" action="{{ route('service-requests.index') }}" method="GET" role="search">
            <div class="field field--search">
                <label for="request-search">Search Service Requests</label>
                <input id="request-search" name="search" type="search" value="{{ $search }}" placeholder="Service type or description">
            </div>
            <div class="field field--status">
                <label for="request-status">Status</label>
                <select id="request-status" name="status">
                    <option value="">All Statuses</option>
                    @foreach ([\App\Models\ServiceRequest::STATUS_PENDING, \App\Models\ServiceRequest::STATUS_IN_PROGRESS, \App\Models\ServiceRequest::STATUS_COMPLETED, \App\Models\ServiceRequest::STATUS_CANCELLED] as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <button class="button button--secondary" type="submit">Apply Filters</button>
            @if (trim($search) !== '' || $status !== '')
                <a class="text-link" href="{{ route('service-requests.index') }}">Clear filters</a>
            @endif
        </form>

        @if ($requests->isEmpty())
            <div class="empty-state">
                <span class="empty-state__icon" aria-hidden="true">◎</span>
                <h3>No Service Requests found.</h3>
                <p>{{ trim($search) === '' && $status === '' ? 'Submit a service request to begin the work queue.' : 'Try another search or status, or clear the filters.' }}</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="request-table">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Resident ID</th>
                            <th scope="col">Service Type</th>
                            <th scope="col">Date Requested</th>
                            <th scope="col">Status</th>
                            <th scope="col">Available Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requests as $serviceRequest)
                            <tr>
                                <td data-label="ID" class="resident-id">#{{ $serviceRequest->id }}</td>
                                <td data-label="Resident ID">#{{ $serviceRequest->residentId }}</td>
                                <td data-label="Service Type" class="request-type">{{ $serviceRequest->serviceType }}</td>
                                <td data-label="Date Requested">{{ $serviceRequest->dateRequested }}</td>
                                <td data-label="Status"><span class="status status--{{ strtolower(str_replace(' ', '-', $serviceRequest->status)) }}">{{ $serviceRequest->status }}</span></td>
                                <td data-label="Available Actions">
                                    <div class="row-actions">
                                        <a class="action-link" href="{{ route('service-requests.show', $serviceRequest->id) }}">View</a>
                                        @include('service-requests.actions')
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
