<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · CSMS</title>
    <link rel="stylesheet" href="{{ asset('css/residents.css') }}">
    <link rel="stylesheet" href="{{ asset('css/service-requests.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="site-header__inner">
            <a class="brand" href="{{ route('service-requests.index') }}" aria-label="Service Request Management home">
                <span class="brand__mark" aria-hidden="true">CS</span>
                <span class="brand__text">Community Services<br><strong>Management System</strong></span>
            </a>
            <nav class="site-nav" aria-label="Management pages">
                <a href="{{ route('residents.index') }}">Residents</a>
                <a href="{{ route('service-requests.index') }}" aria-current="page">Service Requests</a>
            </nav>
        </div>
    </header>

    <main class="page-shell">
        @if (session('success'))
            <div class="notice notice--success" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice notice--error" role="alert">{{ $errors->first() }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
