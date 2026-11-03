@extends('dashboard.layout')

@section('title', 'CSMS Dashboard')

@section('content')
    <div class="page-heading dashboard-heading">
        <div>
            <p class="eyebrow">System overview</p>
            <h1>CSMS Dashboard</h1>
            <p class="intro">A current view of residents and service requests across the community.</p>
        </div>
        <div class="dashboard-links">
            <a class="button button--plain" href="{{ route('residents.index') }}">Manage Residents</a>
            <a class="button button--primary" href="{{ route('service-requests.index') }}">Manage Service Requests</a>
        </div>
    </div>

    <section class="dashboard-section" aria-labelledby="resident-summary-heading">
        <div class="section-heading">
            <div>
                <p class="eyebrow">People</p>
                <h2 id="resident-summary-heading">Resident Summary</h2>
            </div>
        </div>
        <dl class="metric-grid metric-grid--residents">
            <div class="metric-card metric-card--main" data-metric="total-residents">
                <dt>Total Residents</dt><dd>{{ $summary['residents']['total'] }}</dd>
            </div>
            <div class="metric-card" data-metric="active-residents">
                <dt>Active Residents</dt><dd>{{ $summary['residents']['active'] }}</dd>
            </div>
            <div class="metric-card" data-metric="inactive-residents">
                <dt>Inactive Residents</dt><dd>{{ $summary['residents']['inactive'] }}</dd>
            </div>
        </dl>
    </section>

    <section class="dashboard-section" aria-labelledby="request-summary-heading">
        <div class="section-heading">
            <div>
                <p class="eyebrow">Workload</p>
                <h2 id="request-summary-heading">Service Request Summary</h2>
            </div>
        </div>
        <dl class="metric-grid metric-grid--requests">
            <div class="metric-card metric-card--main" data-metric="total-requests">
                <dt>Total Service Requests</dt><dd>{{ $summary['serviceRequests']['total'] }}</dd>
            </div>
            <div class="metric-card metric-card--open" data-metric="open-requests">
                <dt>Open Service Requests</dt><dd>{{ $summary['serviceRequests']['open'] }}</dd>
                <span>Pending + In Progress</span>
            </div>
            <div class="metric-card" data-metric="pending-requests">
                <dt>Pending</dt><dd>{{ $summary['serviceRequests']['pending'] }}</dd>
            </div>
            <div class="metric-card" data-metric="in-progress-requests">
                <dt>In Progress</dt><dd>{{ $summary['serviceRequests']['inProgress'] }}</dd>
            </div>
            <div class="metric-card" data-metric="completed-requests">
                <dt>Completed</dt><dd>{{ $summary['serviceRequests']['completed'] }}</dd>
            </div>
            <div class="metric-card" data-metric="cancelled-requests">
                <dt>Cancelled</dt><dd>{{ $summary['serviceRequests']['cancelled'] }}</dd>
            </div>
        </dl>
    </section>

    <section class="panel dashboard-recent" aria-labelledby="recent-requests-heading">
        <div class="panel__top">
            <div>
                <p class="eyebrow">Latest activity</p>
                <h2 id="recent-requests-heading">Recent Service Requests</h2>
            </div>
            <span class="count">Up to 5 requests</span>
        </div>

        @if ($summary['recentRequests']->isEmpty())
            <div class="empty-state">
                <span class="empty-state__icon" aria-hidden="true">◎</span>
                <h3>No Service Requests available.</h3>
                <p>Submitted requests will appear here when they are available.</p>
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
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($summary['recentRequests'] as $serviceRequest)
                            <tr>
                                <td data-label="ID" class="resident-id">#{{ $serviceRequest->id }}</td>
                                <td data-label="Resident ID">#{{ $serviceRequest->residentId }}</td>
                                <td data-label="Service Type" class="request-type">{{ $serviceRequest->serviceType }}</td>
                                <td data-label="Date Requested">{{ $serviceRequest->dateRequested }}</td>
                                <td data-label="Status"><span class="status status--{{ strtolower(str_replace(' ', '-', $serviceRequest->status)) }}">{{ $serviceRequest->status }}</span></td>
                                <td data-label="Action"><a class="action-link" href="{{ route('service-requests.show', $serviceRequest->id) }}">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
