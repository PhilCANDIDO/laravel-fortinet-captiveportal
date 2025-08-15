<?php

return [
    'status' => [
        'pending' => 'En attente',
        'active' => 'Actif',
        'suspended' => 'Suspendu',
        'expired' => 'Expiré',
        'deleted' => 'Supprimé',
    ],
    
    'fields' => [
        'name' => 'Nom',
        'email' => 'Email',
        'user_type' => 'Type d\'utilisateur',
        'company_name' => 'Société',
        'department' => 'Département',
        'sponsor_name' => 'Nom du sponsor',
        'sponsor_email' => 'Email du sponsor',
        'phone' => 'Téléphone',
        'mobile' => 'Mobile',
        'expires_at' => 'Date d\'expiration',
        'validated_at' => 'Validé le',
        'charter_accepted_at' => 'Charte acceptée le',
        'last_login_at' => 'Dernière connexion',
        'login_count' => 'Nombre de connexions',
        'status' => 'Statut',
        'admin_notes' => 'Notes administrateur',
    ],
    
    'actions' => [
        'create' => 'Créer un utilisateur',
        'edit' => 'Modifier',
        'delete' => 'Supprimer',
        'suspend' => 'Suspendre',
        'reactivate' => 'Réactiver',
        'extend' => 'Prolonger',
        'sync' => 'Synchroniser avec FortiGate',
        'resend_validation' => 'Renvoyer l\'email de validation',
        'view_sessions' => 'Voir les sessions',
    ],
    
    'messages' => [
        'created' => 'Utilisateur créé avec succès',
        'updated' => 'Utilisateur mis à jour avec succès',
        'deleted' => 'Utilisateur supprimé avec succès',
        'suspended' => 'Utilisateur suspendu',
        'reactivated' => 'Utilisateur réactivé',
        'extended' => 'Date d\'expiration prolongée',
        'validation_sent' => 'Email de validation envoyé',
        'synced' => 'Synchronisation avec FortiGate réussie',
        'sync_failed' => 'Échec de la synchronisation avec FortiGate',
    ],
    
    'validation' => [
        'expired' => 'Le lien de validation a expiré',
        'invalid_token' => 'Token de validation invalide',
        'already_validated' => 'Email déjà validé',
        'success' => 'Email validé avec succès',
        'required' => 'Validation email requise',
        'time_remaining' => 'Temps restant pour valider: :minutes minutes',
    ],
    
    'expiration' => [
        'never' => 'Jamais',
        'expired' => 'Expiré',
        'expires_in' => 'Expire dans :time',
        'expires_today' => 'Expire aujourd\'hui',
        'expires_tomorrow' => 'Expire demain',
    ],
];