<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Required</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f9fafb; }
        .card { background: white; padding: 2.5rem; border-radius: 1rem; max-width: 480px; width: 90%; text-align: center; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        h1 { color: #111827; margin: 0 0 .75rem; font-size: 1.5rem; }
        p { color: #6b7280; margin: 0 0 1.5rem; line-height: 1.6; }
        a { display: inline-block; background: #2563eb; color: white; padding: .75rem 2rem; border-radius: .5rem; text-decoration: none; font-weight: 500; }
        a:hover { background: #1d4ed8; }
    </style>
</head>
<body>
<div class="card">
    <h1>License Verification Required</h1>
    <p>
        @if($status === 'expired')
            Your license has expired. Please renew to continue using this software.
        @elseif($status === 'invalid')
            Your license could not be verified. Please check your license key or contact support.
        @else
            Please activate your license to continue using this software.
        @endif
    </p>
    <a href="{{ url('/license/activate') }}">Activate License</a>
</div>
</body>
</html>
