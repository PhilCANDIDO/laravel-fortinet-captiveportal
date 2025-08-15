<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Account Validation</title>
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
            background-color: #2c3e50;
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
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .credentials {
            background-color: #e8f4f8;
            border: 1px solid #b8daff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
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
        <h1>Guest Account Validation</h1>
    </div>
    
    <div class="content">
        <p>Hello {{ $user->name }},</p>
        
        <p>Your guest account has been successfully created. To activate your network access, you must validate your email address.</p>
        
        <div class="warning">
            <strong>⏰ Important:</strong> This validation link expires in {{ $expiresIn }} minutes. After this time, your account will be automatically deleted and you will need to register again.
        </div>
        
        <div style="text-align: center;">
            <a href="{{ $validationUrl }}" class="button">Validate My Account</a>
        </div>
        
        <p>Or copy and paste this link into your browser:</p>
        <p style="word-break: break-all; background-color: #f5f5f5; padding: 10px; border-radius: 3px;">
            {{ $validationUrl }}
        </p>
        
        <div class="credentials">
            <h3>Your Login Credentials:</h3>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Password:</strong> {{ $password }}</p>
            <p style="color: #666; font-size: 14px;">
                <em>Keep this information safe. For security reasons, this password will not be displayed again.</em>
            </p>
        </div>
        
        <h3>Important Information:</h3>
        <ul>
            <li>Your guest access is valid for <strong>24 hours</strong> from validation</li>
            <li>You will need to accept our terms of use on your first login</li>
            <li>Only one device can be connected at a time with your credentials</li>
        </ul>
        
        @if($user->sponsor_email)
        <p>
            <strong>Sponsor:</strong> {{ $user->sponsor_name }} ({{ $user->sponsor_email }})
        </p>
        @endif
    </div>
    
    <div class="footer">
        <p>This email was sent automatically, please do not reply.</p>
        <p>If you did not request this access, you can ignore this email.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</body>
</html>