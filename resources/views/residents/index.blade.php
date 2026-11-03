@extends('residents.layout')

@section('title', 'Resident Management')

@section('content')
    <div class="page-heading">
        <div>
            <p class="eyebrow">Community records</p>
            <h1>Resident Management</h1>
            <p class="intro">Find residents, keep their information current, and manage their active status.</p>
        </div>
        <a class="button button--primary" href="{{ route('residents.create') }}">Register Resident</a>
    </div>

    <section class="panel" aria-labelledby="resident-list-heading">
        <div class="panel__top">
            <div>
                <p class="eyebrow">Directory</p>
                <h2 id="resident-list-heading">Residents</h2>
            </div>
            <span class="count">{{ $residents->count() }} {{ $residents->count() === 1 ? 'record' : 'records' }}</span>
        </div>

        <form class="search-form" action="{{ route('residents.index') }}" method="GET" role="search">
            <div class="field field--search">
                <label for="resident-search">Search Residents</label>
                <input id="resident-search" name="search" type="search" value="{{ $search }}" placeholder="Search first or last name">
            </div>
            <button class="button button--secondary" type="submit">Search</button>
            @if (trim($search) !== '')
                <a class="text-link" href="{{ route('residents.index') }}">Clear search</a>
            @endif
        </form>

        @if ($residents->isEmpty())
            <div class="empty-state">
                <span class="empty-state__icon" aria-hidden="true">◎</span>
                <h3>No Residents found.</h3>
                <p>{{ trim($search) === '' ? 'Register a resident to begin building the directory.' : 'Try another first or last name, or clear the search.' }}</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="resident-table">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Address</th>
                            <th scope="col">Contact Number</th>
                            <th scope="col">Email</th>
                            <th scope="col">Status</th>
                            <th scope="col">Available Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($residents as $resident)
                            <tr>
                                <td data-label="ID" class="resident-id">#{{ $resident->id }}</td>
                                <td data-label="Name" class="resident-name">{{ $resident->firstName }} {{ $resident->lastName }}</td>
                                <td data-label="Address">{{ $resident->address }}</td>
                                <td data-label="Contact Number">{{ $resident->contactNumber }}</td>
                                <td data-label="Email"><span class="email">{{ $resident->email }}</span></td>
                                <td data-label="Status"><span class="status status--{{ strtolower($resident->status) }}">{{ $resident->status }}</span></td>
                                <td data-label="Available Actions">
                                    <div class="row-actions">
                                        <a class="action-link" href="{{ route('residents.edit', $resident->id) }}">Edit</a>
                                        @if ($resident->status === 'Active')
                                            <form action="{{ route('residents.deactivate', $resident->id) }}" method="POST">
                                                @csrf
                                                <button class="action-link action-link--quiet" type="submit">Deactivate</button>
                                            </form>
                                        @endif
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
