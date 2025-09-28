<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Reactivated</title>
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
            background-color: #27ae60;
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
        .success-box {
            background-color: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 15px 0;
        }
        .info-box {
            background-color: #fff;
            border-left: 4px solid #3498db;
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
        <h1>✅ Account Reactivated</h1>
    </div>

    <div class="content">
        <p>Hello {{ $user->name }},</p>

        <p>We are pleased to inform you that your network access account has been <strong>reactivated</strong>.</p>

        <div class="success-box">
            <h3>Your account information:</h3>
            <ul style="margin: 10px 0;">
                <li><strong>Account type:</strong> {{ ucfirst($user->user_type) }}</li>
                <li><strong>Email:</strong> {{ $user->email }}</li>
                @if($user->company_name)
                <li><strong>Company:</strong> {{ $user->company_name }}</li>
                @endif
                @if($user->expires_at)
                <li><strong>Expiration date:</strong> {{ $user->expires_at->format('m/d/Y \a\t H:i') }}</li>
                @endif
                <li><strong>Reactivation date:</strong> {{ now()->format('m/d/Y \a\t H:i') }}</li>
            </ul>
        </div>

        <h3>You can now:</h3>
        <ul>
            <li>Reconnect to the network with your usual credentials</li>
            <li>Access all authorized resources</li>
            <li>Resume your normal activities</li>
        </ul>

        <div class="info-box">
            <h4>Your login credentials:</h4>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p style="color: #666; font-size: 14px;">
                <em>If you have forgotten your password, please contact your network administrator.</em>
            </p>
        </div>

        <div style="background-color: #f0f0f0; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <h4>Need help?</h4>
            <p>If you experience difficulties connecting, please contact your network administrator.</p>
        </div>
    </div>

    <div class="footer">
        <p>This email was sent automatically, please do not reply.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</body>
</html>