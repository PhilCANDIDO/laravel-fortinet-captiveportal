<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account riattivato</title>
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
        <h1>✅ Account riattivato</h1>
    </div>

    <div class="content">
        <p>Ciao {{ $user->name }},</p>

        <p>Siamo lieti di informarti che il tuo account di accesso alla rete è stato <strong>riattivato</strong>.</p>

        <div class="success-box">
            <h3>Informazioni sul tuo account:</h3>
            <ul style="margin: 10px 0;">
                <li><strong>Tipo di account:</strong> {{ ucfirst($user->user_type) }}</li>
                <li><strong>Email:</strong> {{ $user->email }}</li>
                @if($user->company_name)
                <li><strong>Azienda:</strong> {{ $user->company_name }}</li>
                @endif
                @if($user->expires_at)
                <li><strong>Data di scadenza:</strong> {{ $user->expires_at->format('d/m/Y \a\l\l\e H:i') }}</li>
                @endif
                <li><strong>Data di riattivazione:</strong> {{ now()->format('d/m/Y \a\l\l\e H:i') }}</li>
            </ul>
        </div>

        <h3>Ora puoi:</h3>
        <ul>
            <li>Riconnetterti alla rete con le tue credenziali abituali</li>
            <li>Accedere a tutte le risorse autorizzate</li>
            <li>Riprendere le tue normali attività</li>
        </ul>

        <div class="info-box">
            <h4>Le tue credenziali di accesso:</h4>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p style="color: #666; font-size: 14px;">
                <em>Se hai dimenticato la password, contatta il tuo amministratore di rete.</em>
            </p>
        </div>

        <div style="background-color: #f0f0f0; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <h4>Hai bisogno di aiuto?</h4>
            <p>Se hai difficoltà a connetterti, contatta il tuo amministratore di rete.</p>
        </div>
    </div>

    <div class="footer">
        <p>Questa email è stata inviata automaticamente, si prega di non rispondere.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</body>
</html>