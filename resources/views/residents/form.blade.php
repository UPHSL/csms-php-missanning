@extends('residents.layout')

@php($editing = $resident !== null)

@section('title', $editing ? 'Edit Resident' : 'Register Resident')

@section('content')
    <a class="back-link" href="{{ route('residents.index') }}">← Back to Resident Management</a>

    <div class="page-heading page-heading--form">
        <div>
            <p class="eyebrow">{{ $editing ? 'Update record' : 'New record' }}</p>
            <h1>{{ $editing ? 'Edit Resident' : 'Register Resident' }}</h1>
            <p class="intro">{{ $editing ? 'Update the resident’s contact and identity information below.' : 'Enter the resident’s information to add them to the directory.' }}</p>
        </div>
    </div>

    <section class="panel form-panel">
        @if ($editing)
            <div class="record-meta">
                <span>Resident ID <strong>#{{ $resident->id }}</strong></span>
                <span>Status: <span class="status status--{{ strtolower($resident->status) }}">{{ $resident->status }}</span></span>
            </div>
        @else
            <p class="form-note">New residents are registered as <strong>Active</strong>. Their ID is assigned automatically.</p>
        @endif

        @if ($errors->any())
            <div class="notice notice--error" role="alert">
                Please review the highlighted fields and try again.
            </div>
        @endif

        <form action="{{ $editing ? route('residents.update', $resident->id) : route('residents.store') }}" method="POST">
            @csrf
            @if ($editing)
                @method('PATCH')
            @endif

            <div class="form-grid">
                <div class="field">
                    <label for="firstName">First Name</label>
                    <input id="firstName" name="firstName" type="text" value="{{ old('firstName', $resident?->firstName) }}" aria-invalid="{{ $errors->has('first_name') ? 'true' : 'false' }}" @if ($errors->has('first_name')) aria-describedby="firstName-error" @endif>
                    @error('first_name') <p class="field-error" id="firstName-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="lastName">Last Name</label>
                    <input id="lastName" name="lastName" type="text" value="{{ old('lastName', $resident?->lastName) }}" aria-invalid="{{ $errors->has('last_name') ? 'true' : 'false' }}" @if ($errors->has('last_name')) aria-describedby="lastName-error" @endif>
                    @error('last_name') <p class="field-error" id="lastName-error">{{ $message }}</p> @enderror
                </div>
                <div class="field field--full">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3" aria-invalid="{{ $errors->has('address') ? 'true' : 'false' }}" @if ($errors->has('address')) aria-describedby="address-error" @endif>{{ old('address', $resident?->address) }}</textarea>
                    @error('address') <p class="field-error" id="address-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="contactNumber">Contact Number</label>
                    <input id="contactNumber" name="contactNumber" type="tel" value="{{ old('contactNumber', $resident?->contactNumber) }}" inputmode="numeric" aria-invalid="{{ $errors->has('contact_number') ? 'true' : 'false' }}" @if ($errors->has('contact_number')) aria-describedby="contactNumber-error" @endif>
                    @error('contact_number') <p class="field-error" id="contactNumber-error">{{ $message }}</p> @enderror
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $resident?->email) }}" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @if ($errors->has('email')) aria-describedby="email-error" @endif>
                    @error('email') <p class="field-error" id="email-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="form-actions">
                <button class="button button--primary" type="submit">{{ $editing ? 'Save Changes' : 'Register Resident' }}</button>
                <a class="button button--plain" href="{{ route('residents.index') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
