@extends('layouts.email')

@section('content')
<h1>Vos identifiants de connexion</h1>

<p>Bonjour {{ $user->name }},</p>

<p>Votre compte invité a été créé avec succès. Vous pouvez maintenant accéder au réseau WiFi.</p>

<h2>Vos identifiants de connexion :</h2>

<div style="background-color: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <p><strong>Nom d'utilisateur :</strong> {{ $username }}</p>
    <p><strong>Mot de passe :</strong> {{ $password }}</p>
</div>

<p><strong>Important :</strong> Conservez ces identifiants en lieu sûr. Vous en aurez besoin pour vous connecter au réseau WiFi.</p>

<h2>Comment se connecter :</h2>
<ol>
    <li>Connectez-vous au réseau WiFi</li>
    <li>Ouvrez votre navigateur web</li>
    <li>Vous serez automatiquement redirigé vers le portail captif</li>
    <li>Entrez vos identifiants ci-dessus</li>
    <li>Acceptez les conditions d'utilisation</li>
</ol>

@if($captivePortalUrl)
<p>Ou accédez directement au portail captif : <a href="{{ $captivePortalUrl }}">{{ $captivePortalUrl }}</a></p>
@endif

<h2>Informations importantes :</h2>
<ul>
    <li>Votre compte est valide pour 24 heures</li>
    <li>Après expiration, vous devrez créer un nouveau compte</li>
    <li>En cas de problème, contactez l'administrateur réseau</li>
</ul>

<p>Cordialement,<br>
L'équipe informatique</p>
@endsection