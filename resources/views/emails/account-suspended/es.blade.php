<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta suspendida</title>
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
        <h1>⚠️ Cuenta suspendida</h1>
    </div>

    <div class="content">
        <p>Hola {{ $user->name }},</p>

        <p>Le informamos que su cuenta de acceso a la red ha sido <strong>suspendida</strong>.</p>

        <div class="warning-box">
            <h3>Información importante:</h3>
            <ul style="margin: 10px 0;">
                <li><strong>Tipo de cuenta:</strong> {{ ucfirst($user->user_type) }}</li>
                <li><strong>Correo:</strong> {{ $user->email }}</li>
                @if($user->company_name)
                <li><strong>Empresa:</strong> {{ $user->company_name }}</li>
                @endif
                <li><strong>Fecha de suspensión:</strong> {{ now()->format('d/m/Y \a \l\a\s H:i') }}</li>
            </ul>
        </div>

        <h3>Consecuencias de la suspensión:</h3>
        <ul>
            <li>Ya no podrá conectarse a la red</li>
            <li>Todas sus sesiones activas han sido cerradas</li>
            <li>Su acceso está temporalmente deshabilitado</li>
        </ul>

        <div class="info-box">
            <h4>¿Qué hacer ahora?</h4>
            <p>Si cree que esta suspensión es un error o desea aclaraciones, comuníquese con su administrador de red.</p>
        </div>

        @if($reason ?? false)
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <h4>Motivo de la suspensión:</h4>
            <p>{{ $reason }}</p>
        </div>
        @endif
    </div>

    <div class="footer">
        <p>Este correo fue enviado automáticamente, por favor no responda.</p>
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>
</body>
</html>