<nav class="site-nav" aria-label="Management pages">
    <a href="{{ route('dashboard.index') }}" @if ($current === 'dashboard') aria-current="page" @endif>Dashboard</a>
    <a href="{{ route('residents.index') }}" @if ($current === 'residents') aria-current="page" @endif>Residents</a>
    <a href="{{ route('service-requests.index') }}" @if ($current === 'service-requests') aria-current="page" @endif>Service Requests</a>
</nav>
