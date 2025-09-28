<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account sospeso</title>
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
        <h1>⚠️ Account sospeso</h1>
    </div>

    <div class="content">
        <p>Ciao {{ $user->name }},</p>

        <p>Ti informiamo che il tuo account di accesso alla rete è stato <strong>sospeso</strong>.</p>

        <div class="warning-box">
            <h3>Informazioni importanti:</h3>
            <ul style="margin: 10px 0;">
                <li><strong>Tipo di account:</strong> {{ ucfirst($user->user_type) }}</li>
                <li><strong>Email:</strong> {{ $user->email }}</li>
                @if($user->company_name)
                <li><strong>Azienda:</strong> {{ $user->company_name }}</li>
                @endif
                <li><strong>Data di sospensione:</strong> {{ now()->format('d/m/Y \a\l\l\e H:i') }}</li>
            </ul>
        </div>

        <h3>Conseguenze della sospensione:</h3>
        <ul>
            <li>Non potrai più connetterti alla rete</li>
            <li>Tutte le tue sessioni attive sono state chiuse</li>
            <li>Il tuo accesso è temporaneamente disabilitato</li>
        </ul>

        <div class="info-box">
            <h4>Cosa fare ora?</h4>
            <p>Se ritieni che questa sospensione sia un errore o desideri chiarimenti, contatta il tuo amministratore di rete.</p>
        </div>

        @if($reason ?? false)
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <h4>Motivo della sospensione:</h4>
            <p>{{ $reason }}</p>
        </div>
        @endif
    </div>

    <div class="footer">
        <p>Questa email è stata inviata automaticamente, si prega di non rispondere.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</body>
</html>