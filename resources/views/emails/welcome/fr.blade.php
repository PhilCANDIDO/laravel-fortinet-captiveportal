<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue - Vos identifiants de connexion</title>
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
        .credentials {
            background-color: #e8f4f8;
            border: 1px solid #b8daff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
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
        <h1>Bienvenue !</h1>
    </div>
    
    <div class="content">
        <p>Bonjour {{ $user->name }},</p>
        
        <p>Votre compte <strong>{{ $userType }}</strong> a été créé avec succès. Vous pouvez maintenant accéder au réseau avec les identifiants ci-dessous.</p>
        
        <div class="credentials">
            <h3>Vos identifiants de connexion :</h3>
            <p><strong>Email :</strong> {{ $user->email }}</p>
            <p><strong>Mot de passe :</strong> {{ $password }}</p>
            <p><strong>URL de connexion :</strong> <a href="{{ $loginUrl }}">{{ $loginUrl }}</a></p>
            <p style="color: #666; font-size: 14px;">
                <em>Conservez ces informations en lieu sûr. Pour des raisons de sécurité, ce mot de passe ne sera plus affiché.</em>
            </p>
        </div>
        
        <div class="info-box">
            <h3>Informations sur votre compte :</h3>
            <ul style="margin: 10px 0;">
                <li><strong>Type de compte :</strong> {{ $userType }}</li>
                @if($user->company_name)
                <li><strong>Société :</strong> {{ $user->company_name }}</li>
                @endif
                @if($expiresAt)
                <li><strong>Date d'expiration :</strong> {{ $expiresAt->format('d/m/Y à H:i') }}</li>
                @else
                <li><strong>Date d'expiration :</strong> Pas d'expiration</li>
                @endif
            </ul>
        </div>
        
        <h3>Prochaines étapes :</h3>
        <ol>
            <li>Connectez-vous avec vos identifiants</li>
            @if($user->user_type !== 'employee')
            <li>Acceptez notre charte d'utilisation</li>
            @endif
            <li>Profitez de votre accès au réseau</li>
        </ol>
        
        <div style="background-color: #f0f0f0; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <h4>Besoin d'aide ?</h4>
            <p>Si vous rencontrez des difficultés pour vous connecter, contactez votre administrateur réseau.</p>
        </div>
    </div>
    
    <div class="footer">
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</body>
</html>