<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta reactivada</title>
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
        <h1>✅ Cuenta reactivada</h1>
    </div>

    <div class="content">
        <p>Hola {{ $user->name }},</p>

        <p>Nos complace informarle que su cuenta de acceso a la red ha sido <strong>reactivada</strong>.</p>

        <div class="success-box">
            <h3>Información de su cuenta:</h3>
            <ul style="margin: 10px 0;">
                <li><strong>Tipo de cuenta:</strong> {{ ucfirst($user->user_type) }}</li>
                <li><strong>Correo:</strong> {{ $user->email }}</li>
                @if($user->company_name)
                <li><strong>Empresa:</strong> {{ $user->company_name }}</li>
                @endif
                @if($user->expires_at)
                <li><strong>Fecha de expiración:</strong> {{ $user->expires_at->format('d/m/Y \a \l\a\s H:i') }}</li>
                @endif
                <li><strong>Fecha de reactivación:</strong> {{ now()->format('d/m/Y \a \l\a\s H:i') }}</li>
            </ul>
        </div>

        <h3>Ahora puede:</h3>
        <ul>
            <li>Volver a conectarse a la red con sus credenciales habituales</li>
            <li>Acceder a todos los recursos autorizados</li>
            <li>Reanudar sus actividades normales</li>
        </ul>

        <div class="info-box">
            <h4>Sus credenciales de acceso:</h4>
            <p><strong>Correo:</strong> {{ $user->email }}</p>
            <p style="color: #666; font-size: 14px;">
                <em>Si ha olvidado su contraseña, comuníquese con su administrador de red.</em>
            </p>
        </div>

        <div style="background-color: #f0f0f0; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <h4>¿Necesita ayuda?</h4>
            <p>Si tiene dificultades para conectarse, comuníquese con su administrador de red.</p>
        </div>
    </div>

    <div class="footer">
        <p>Este correo fue enviado automáticamente, por favor no responda.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</body>
</html>