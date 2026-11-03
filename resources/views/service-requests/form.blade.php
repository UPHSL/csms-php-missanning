@extends('service-requests.layout')

@section('title', 'Submit Service Request')

@section('content')
    <a class="back-link" href="{{ route('service-requests.index') }}">← Back to Service Request Management</a>

    <div class="page-heading page-heading--form">
        <div>
            <p class="eyebrow">New request</p>
            <h1>Submit Service Request</h1>
            <p class="intro">Record a service need for an Active Resident. New requests begin as Pending.</p>
        </div>
    </div>

    <section class="panel form-panel">
        <p class="form-note">Need the Resident ID? <a href="{{ route('residents.index') }}">View Residents</a>. Eligibility is checked when you submit.</p>

        <form action="{{ route('service-requests.store') }}" method="POST">
            @csrf
            <div class="form-grid">
                <div class="field">
                    <label for="residentId">Resident ID</label>
                    <input id="residentId" name="residentId" type="number" min="1" step="1" value="{{ old('residentId') }}" aria-invalid="{{ $errors->has('residentId') ? 'true' : 'false' }}" @if ($errors->has('residentId')) aria-describedby="residentId-error" @endif>
                    @error('residentId') <p class="field-error" id="residentId-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="dateRequested">Date Requested</label>
                    <input id="dateRequested" name="dateRequested" type="date" value="{{ old('dateRequested') }}" aria-invalid="{{ $errors->has('dateRequested') ? 'true' : 'false' }}" @if ($errors->has('dateRequested')) aria-describedby="dateRequested-error" @endif>
                    @error('dateRequested') <p class="field-error" id="dateRequested-error">{{ $message }}</p> @enderror
                </div>
                <div class="field field--full">
                    <label for="serviceType">Service Type</label>
                    <input id="serviceType" name="serviceType" type="text" value="{{ old('serviceType') }}" aria-invalid="{{ $errors->has('serviceType') ? 'true' : 'false' }}" @if ($errors->has('serviceType')) aria-describedby="serviceType-error" @endif>
                    @error('serviceType') <p class="field-error" id="serviceType-error">{{ $message }}</p> @enderror
                </div>
                <div class="field field--full">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" aria-invalid="{{ $errors->has('description') ? 'true' : 'false' }}" @if ($errors->has('description')) aria-describedby="description-error" @endif>{{ old('description') }}</textarea>
                    @error('description') <p class="field-error" id="description-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="form-actions">
                <button class="button button--primary" type="submit">Submit Service Request</button>
                <a class="button button--plain" href="{{ route('service-requests.index') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
