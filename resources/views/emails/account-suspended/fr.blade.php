<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte suspendu</title>
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
        <h1>⚠️ Compte suspendu</h1>
    </div>

    <div class="content">
        <p>Bonjour {{ $user->name }},</p>

        <p>Nous vous informons que votre compte d'accès au réseau a été <strong>suspendu</strong>.</p>

        <div class="warning-box">
            <h3>Informations importantes :</h3>
            <ul style="margin: 10px 0;">
                <li><strong>Type de compte :</strong> {{ ucfirst($user->user_type) }}</li>
                <li><strong>Email :</strong> {{ $user->email }}</li>
                @if($user->company_name)
                <li><strong>Société :</strong> {{ $user->company_name }}</li>
                @endif
                <li><strong>Date de suspension :</strong> {{ now()->format('d/m/Y à H:i') }}</li>
            </ul>
        </div>

        <h3>Conséquences de la suspension :</h3>
        <ul>
            <li>Vous ne pourrez plus vous connecter au réseau</li>
            <li>Toutes vos sessions actives ont été fermées</li>
            <li>Votre accès est temporairement désactivé</li>
        </ul>

        <div class="info-box">
            <h4>Que faire maintenant ?</h4>
            <p>Si vous pensez que cette suspension est une erreur ou si vous souhaitez des éclaircissements, veuillez contacter votre administrateur réseau.</p>
        </div>

        @if($reason ?? false)
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <h4>Raison de la suspension :</h4>
            <p>{{ $reason }}</p>
        </div>
        @endif
    </div>

    <div class="footer">
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</body>
</html>