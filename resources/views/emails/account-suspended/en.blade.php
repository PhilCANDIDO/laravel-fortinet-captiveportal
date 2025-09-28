<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspended</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #e74c3c;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .warning-box {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }
        .info-box {
            background-color: #fff;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin: 15px 0;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>⚠️ Account Suspended</h1>
    </div>

    <div class="content">
        <p>Hello {{ $user->name }},</p>

        <p>We are writing to inform you that your network access account has been <strong>suspended</strong>.</p>

        <div class="warning-box">
            <h3>Important information:</h3>
            <ul style="margin: 10px 0;">
                <li><strong>Account type:</strong> {{ ucfirst($user->user_type) }}</li>
                <li><strong>Email:</strong> {{ $user->email }}</li>
                @if($user->company_name)
                <li><strong>Company:</strong> {{ $user->company_name }}</li>
                @endif
                <li><strong>Suspension date:</strong> {{ now()->format('m/d/Y \a\t H:i') }}</li>
            </ul>
        </div>

        <h3>Consequences of suspension:</h3>
        <ul>
            <li>You will no longer be able to connect to the network</li>
            <li>All your active sessions have been closed</li>
            <li>Your access is temporarily disabled</li>
        </ul>

        <div class="info-box">
            <h4>What to do now?</h4>
            <p>If you believe this suspension is an error or if you would like clarification, please contact your network administrator.</p>
        </div>

        @if($reason ?? false)
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <h4>Reason for suspension:</h4>
            <p>{{ $reason }}</p>
        </div>
        @endif
    </div>

    <div class="footer">
        <p>This email was sent automatically, please do not reply.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</body>
</html>