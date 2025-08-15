<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation de votre compte invité</title>
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
        <h1>Validation de votre compte invité</h1>
    </div>
    
    <div class="content">
        <p>Bonjour {{ $user->name }},</p>
        
        <p>Votre compte invité a été créé avec succès. Pour activer votre accès au réseau, vous devez valider votre adresse email.</p>
        
        <div class="warning">
            <strong>⏰ Important :</strong> Ce lien de validation expire dans {{ $expiresIn }} minutes. Après ce délai, votre compte sera automatiquement supprimé et vous devrez recommencer l'inscription.
        </div>
        
        <div style="text-align: center;">
            <a href="{{ $validationUrl }}" class="button">Valider mon compte</a>
        </div>
        
        <p>Ou copiez et collez ce lien dans votre navigateur :</p>
        <p style="word-break: break-all; background-color: #f5f5f5; padding: 10px; border-radius: 3px;">
            {{ $validationUrl }}
        </p>
        
        <div class="credentials">
            <h3>Vos identifiants de connexion :</h3>
            <p><strong>Email :</strong> {{ $user->email }}</p>
            <p><strong>Mot de passe :</strong> {{ $password }}</p>
            <p style="color: #666; font-size: 14px;">
                <em>Conservez ces informations en lieu sûr. Pour des raisons de sécurité, ce mot de passe ne sera plus affiché.</em>
            </p>
        </div>
        
        <h3>Informations importantes :</h3>
        <ul>
            <li>Votre accès invité est valable pour <strong>24 heures</strong> à partir de la validation</li>
            <li>Vous devrez accepter notre charte d'utilisation lors de votre première connexion</li>
            <li>Un seul appareil peut être connecté à la fois avec vos identifiants</li>
        </ul>
        
        @if($user->sponsor_email)
        <p>
            <strong>Parrain :</strong> {{ $user->sponsor_name }} ({{ $user->sponsor_email }})
        </p>
        @endif
    </div>
    
    <div class="footer">
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        <p>Si vous n'avez pas demandé cet accès, vous pouvez ignorer cet email.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</body>
</html>