<?php

return [
    'user_types' => [
        'employee' => 'Employé',
        'consultant' => 'Consultant',
        'guest' => 'Invité',
    ],
    
    'errors' => [
        'general' => 'Une erreur est survenue avec l\'API FortiGate',
        'unauthorized' => 'Authentification FortiGate échouée',
        'forbidden' => 'Accès refusé à la ressource FortiGate',
        'not_found' => 'Ressource FortiGate non trouvée',
        'rate_limited' => 'Trop de requêtes vers l\'API FortiGate',
        'server_error' => 'Erreur serveur FortiGate',
        'timeout' => 'Délai d\'attente FortiGate dépassé',
        'connection_failed' => 'Impossible de se connecter à FortiGate',
        'user_exists' => 'L\'utilisateur existe déjà dans FortiGate',
        'user_not_found' => 'Utilisateur non trouvé dans FortiGate',
        'sync_failed' => 'Échec de la synchronisation avec FortiGate',
    ],
    
    'success' => [
        'user_created' => 'Utilisateur créé avec succès dans FortiGate',
        'user_updated' => 'Utilisateur mis à jour dans FortiGate',
        'user_deleted' => 'Utilisateur supprimé de FortiGate',
        'user_enabled' => 'Utilisateur activé dans FortiGate',
        'user_disabled' => 'Utilisateur désactivé dans FortiGate',
        'session_terminated' => 'Session utilisateur terminée',
        'sync_completed' => 'Synchronisation FortiGate terminée',
    ],
    
    'status' => [
        'synced' => 'Synchronisé',
        'pending' => 'En attente',
        'error' => 'Erreur',
        'not_synced' => 'Non synchronisé',
    ],
    
    'actions' => [
        'sync' => 'Synchroniser',
        'force_sync' => 'Forcer la synchronisation',
        'terminate_session' => 'Terminer la session',
        'view_sessions' => 'Voir les sessions',
    ],
    
    'session' => [
        'active' => 'Session active',
        'inactive' => 'Aucune session active',
        'terminated' => 'Session terminée',
        'ip_address' => 'Adresse IP',
        'start_time' => 'Début de session',
        'duration' => 'Durée',
    ],
];