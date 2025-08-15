<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rappel - Votre accès expire bientôt</title>
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
            background-color: #f39c12;
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
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: center;
        }
        .countdown {
            font-size: 36px;
            font-weight: bold;
            color: #e74c3c;
            text-align: center;
            margin: 20px 0;
        }
        .action-box {
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
        <h1>⚠️ Rappel d'expiration</h1>
    </div>
    
    <div class="content">
        <p>Bonjour {{ $user->name }},</p>
        
        <div class="warning">
            <h2 style="margin: 0;">Votre accès {{ $userType }} expire bientôt !</h2>
        </div>
        
        <div class="countdown">
            @if($daysRemaining == 1)
                DEMAIN
            @else
                {{ $daysRemaining }} JOURS
            @endif
        </div>
        
        <p style="text-align: center; font-size: 18px;">
            Date d'expiration : <strong>{{ $expiresAt->format('d/m/Y à H:i') }}</strong>
        </p>
        
        <div class="action-box">
            <h3>Que se passe-t-il à l'expiration ?</h3>
            <ul>
                <li>Votre accès au réseau sera automatiquement désactivé</li>
                <li>Vous ne pourrez plus vous connecter avec vos identifiants actuels</li>
                <li>Toutes vos sessions actives seront terminées</li>
            </ul>
        </div>
        
        @if($user->user_type === 'consultant')
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3>Besoin de prolonger votre accès ?</h3>
            <p>Contactez votre responsable ou l'administrateur réseau pour demander une extension de votre accès.</p>
        </div>
        @endif
        
        @if($user->user_type === 'guest')
        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3>Besoin d'un accès supplémentaire ?</h3>
            <p>Les accès invités sont limités à 24 heures. Si vous avez besoin d'un accès plus long, veuillez contacter votre sponsor : {{ $user->sponsor_email ?? 'votre responsable' }}</p>
        </div>
        @endif
        
        <div style="background-color: #f0f0f0; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <h4>Informations sur votre compte :</h4>
            <ul style="margin: 10px 0;">
                <li><strong>Type de compte :</strong> {{ $userType }}</li>
                <li><strong>Email :</strong> {{ $user->email }}</li>
                @if($user->company_name)
                <li><strong>Société :</strong> {{ $user->company_name }}</li>
                @endif
            </ul>
        </div>
    </div>
    
    <div class="footer">
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</body>
</html>