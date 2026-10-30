<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $application }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f5f6f8;
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
        }

        .container {
            width: min(850px, 90%);
            background: white;
            border: 1px solid #d9dde3;
            border-radius: 18px;
            padding: 48px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        }

        .label {
            margin: 0 0 36px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        h1 {
            margin: 0 0 20px;
            font-size: clamp(2.5rem, 6vw, 4rem);
            line-height: 1.05;
        }

        .message {
            font-size: 1.15rem;
            margin-bottom: 32px;
        }

        .details {
            display: grid;
            gap: 16px;
            margin-bottom: 32px;
        }

        .detail {
            background: #f7f8fa;
            border-radius: 14px;
            padding: 20px;
        }

        .detail strong {
            display: block;
            margin-bottom: 10px;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .detail span {
            font-size: 1.05rem;
        }

        .status {
            font-weight: 700;
            font-size: 1.05rem;
        }

        .resident-link {
            display: inline-block;
            margin-top: 24px;
            padding: 12px 18px;
            border-radius: 8px;
            background: #116957;
            color: white;
            font-weight: 700;
            text-decoration: none;
        }

        .resident-link:focus-visible {
            outline: 3px solid #d49b48;
            outline-offset: 3px;
        }
    </style>
</head>

<body>

<div class="container">

    <p class="label">
        Programming Languages Laboratory
    </p>

    <h1>
        {{ $application }}
    </h1>

    <p class="message">
        Starter application initialized successfully.
    </p>

    <div class="details">

        <div class="detail">
            <strong>Current Sprint</strong>
            <span>{{ $sprint }}</span>
        </div>

        <div class="detail">
            <strong>Technology</strong>
            <span>{{ $technology }}</span>
        </div>

        <div class="detail">
            <strong>Version</strong>
            <span>{{ $version }}</span>
        </div>

    </div>

    <p class="status">
        Environment status: Ready
    </p>

    <a class="resident-link" href="{{ route('residents.index') }}">
        Open Resident Management
    </a>

    <a class="resident-link" href="{{ route('service-requests.index') }}">
        Open Service Request Management
    </a>

</div>

</body>
</html>
