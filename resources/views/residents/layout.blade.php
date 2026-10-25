<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · CSMS</title>
    <link rel="stylesheet" href="{{ asset('css/residents.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="site-header__inner">
            <a class="brand" href="{{ route('residents.index') }}" aria-label="Resident Management home">
                <span class="brand__mark" aria-hidden="true">CS</span>
                <span class="brand__text">Community Services<br><strong>Management System</strong></span>
            </a>
            <span class="site-header__label">Resident records</span>
        </div>
    </header>

    <main class="page-shell">
        @if (session('success'))
            <div class="notice notice--success" role="status">{{ session('success') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
