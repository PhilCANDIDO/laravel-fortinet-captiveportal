@extends('layouts.email')

@section('content')
<h1>Your Login Credentials</h1>

<p>Hello {{ $user->name }},</p>

<p>Your guest account has been successfully created. You can now access the WiFi network.</p>

<h2>Your login credentials:</h2>

<div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <p><strong>Username:</strong> {{ $username }}</p>
    <p><strong>Password:</strong> {{ $password }}</p>
</div>

<p><strong>Important:</strong> Keep these credentials in a safe place. You will need them to connect to the WiFi network.</p>

<h2>How to connect:</h2>
<ol>
    <li>Connect to the WiFi network</li>
    <li>Open your web browser</li>
    <li>You will be automatically redirected to the captive portal</li>
    <li>Enter your credentials above</li>
    <li>Accept the terms of use</li>
</ol>

@if($captivePortalUrl)
<p>Or directly access the captive portal: <a href="{{ $captivePortalUrl }}">{{ $captivePortalUrl }}</a></p>
@endif

<h2>Important information:</h2>
<ul>
    <li>Your account is valid for 24 hours</li>
    <li>After expiration, you will need to create a new account</li>
    <li>If you have any issues, contact the network administrator</li>
</ul>

<p>Best regards,<br>
The IT Team</p>
@endsection