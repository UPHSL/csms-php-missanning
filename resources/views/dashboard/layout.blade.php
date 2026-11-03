<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · CSMS</title>
    <link rel="stylesheet" href="{{ asset('css/residents.css') }}">
    <link rel="stylesheet" href="{{ asset('css/service-requests.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="site-header__inner">
            <a class="brand" href="{{ route('dashboard.index') }}" aria-label="CSMS Dashboard home">
                <span class="brand__mark" aria-hidden="true">CS</span>
                <span class="brand__text">Community Services<br><strong>Management System</strong></span>
            </a>
            @include('partials.management-nav', ['current' => 'dashboard'])
        </div>
    </header>

    <main class="page-shell">
        @yield('content')
    </main>
</body>
</html>
