<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Ending Soon — {{ config('app.name') }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f8fafc;
        }

        .header {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            padding: 24px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 24px;
        }

        .header h1 {
            margin: 0 0 8px;
            font-size: 22px;
        }

        .header p {
            margin: 0;
            opacity: 0.9;
        }

        .content {
            background: white;
            padding: 28px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .alert-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-box strong {
            color: #92400e;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .detail-label {
            font-weight: 600;
            color: #475569;
        }

        .cta {
            text-align: center;
            margin: 28px 0;
        }

        .btn {
            display: inline-block;
            padding: 14px 32px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 15px;
        }

        .footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #6b7280;
            font-size: 13px;
        }

        .footer a {
            color: #2563eb;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <p>Subscription Notification</p>
    </div>

    <div class="content">
        <p>Hello {{ $tenantName ?? 'there' }},</p>

        <div class="alert-box">
            <strong>Your free trial ends in {{ max(0, $daysRemaining) }} day{{ $daysRemaining !== 1 ? 's' : '' }}.</strong><br>
            To avoid any interruption to your workspace, please add a payment method before <strong>{{ $trialEndsAt->format('F j, Y') }}</strong>.
        </div>

        <p>Once your trial ends, your workspace will remain accessible for a short grace period. After that, access to your data and services will be temporarily suspended until payment is set up.</p>

        <div class="cta">
            <a href="{{ $billingUrl }}" class="btn">Add Payment Method</a>
        </div>

        <p style="margin-top: 20px; color: #64748b; font-size: 13px;">
            If you have questions or need help choosing the right plan, our support team is here for you.
        </p>
    </div>

    <div class="footer">
        <p>Need help? <a href="{{ $supportUrl }}">Contact Support</a></p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
    </div>
</body>

</html>
