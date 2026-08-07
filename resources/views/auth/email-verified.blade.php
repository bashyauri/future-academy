<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $success ? 'Email Verified' : 'Verification Failed' }} — Future Academy</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #0f0f14;
            color: #f5f5f7;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            background: #1c1c28;
            border: 1px solid #2a2a3d;
            border-radius: 20px;
            padding: 48px 40px;
            max-width: 440px;
            width: 100%;
            text-align: center;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.5);
        }

        .icon {
            width: 72px;
            height: 72px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin-bottom: 28px;
            background: {{ $success ? 'rgba(52, 211, 153, 0.15)' : 'rgba(248, 113, 113, 0.15)' }};
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.3px;
            margin-bottom: 12px;
            color: #f5f5f7;
        }

        p {
            font-size: 15px;
            line-height: 1.6;
            color: #a0a0b8;
            margin-bottom: 32px;
        }

        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            background: {{ $success ? 'rgba(52, 211, 153, 0.15)' : 'rgba(248, 113, 113, 0.15)' }};
            color: {{ $success ? '#34d399' : '#f87171' }};
        }

        .hint {
            font-size: 13px;
            color: #60607a;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">{{ $success ? '✅' : '❌' }}</div>

        <h1>{{ $success ? 'Email Verified!' : 'Verification Failed' }}</h1>

        <p>{{ $message }}</p>

        <span class="badge">{{ $success ? 'Verification complete' : 'Link invalid or expired' }}</span>

        @if($success)
            <p class="hint">You can close this tab and return to the Future Academy app.</p>
        @else
            <p class="hint">Please open the app and request a new verification email.</p>
        @endif
    </div>
</body>
</html>
