<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte réactivé</title>
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
        <h1>✅ Compte réactivé</h1>
    </div>

    <div class="content">
        <p>Bonjour {{ $user->name }},</p>

        <p>Nous sommes heureux de vous informer que votre compte d'accès au réseau a été <strong>réactivé</strong>.</p>

        <div class="success-box">
            <h3>Informations de votre compte :</h3>
            <ul style="margin: 10px 0;">
                <li><strong>Type de compte :</strong> {{ ucfirst($user->user_type) }}</li>
                <li><strong>Email :</strong> {{ $user->email }}</li>
                @if($user->company_name)
                <li><strong>Société :</strong> {{ $user->company_name }}</li>
                @endif
                @if($user->expires_at)
                <li><strong>Date d'expiration :</strong> {{ $user->expires_at->format('d/m/Y à H:i') }}</li>
                @endif
                <li><strong>Date de réactivation :</strong> {{ now()->format('d/m/Y à H:i') }}</li>
            </ul>
        </div>

        <h3>Vous pouvez maintenant :</h3>
        <ul>
            <li>Vous reconnecter au réseau avec vos identifiants habituels</li>
            <li>Accéder à toutes les ressources autorisées</li>
            <li>Reprendre vos activités normalement</li>
        </ul>

        <div class="info-box">
            <h4>Vos identifiants de connexion :</h4>
            <p><strong>Email :</strong> {{ $user->email }}</p>
            <p style="color: #666; font-size: 14px;">
                <em>Si vous avez oublié votre mot de passe, contactez votre administrateur réseau.</em>
            </p>
        </div>

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