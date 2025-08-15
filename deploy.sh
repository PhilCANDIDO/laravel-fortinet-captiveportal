#!/bin/bash

#############################################################
# MyDatabase Deployment Script
# Idempotent script for GitLab CI/CD deployments
# 
# Usage:
#   ./deploy.sh [options]
#
# Options:
#   --php-fpm-service=NAME   PHP-FPM service name (default: php8.4-fpm)
#   --webuser=USER           Web server user (default: apache for RHEL or CentOS, www-data for Debian or Ubuntu)
#   --force-git-rebase       Force reset to remote branch (overwrites local changes)
#   --backup-cron            Setup daily backup cron job at 23:00
#   --help                   Show this help message
#
# Examples:
#   ./deploy.sh
#   ./deploy.sh --php-fpm-service=php8.2-fpm --webuser=nginx
#   ./deploy.sh --force-git-rebase
#   ./deploy.sh --backup-cron
#############################################################

# Default configuration
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PHP_FPM_SERVICE="php8.4-fpm"
WEBUSER="apache"
FORCE_GIT_REBASE=false
SETUP_BACKUP_CRON=false

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --php-fpm-service=*)
            PHP_FPM_SERVICE="${1#*=}"
            shift
            ;;
        --php-fpm-service)
            PHP_FPM_SERVICE="$2"
            shift 2
            ;;
        --webuser=*)
            WEBUSER="${1#*=}"
            shift
            ;;
        --webuser)
            WEBUSER="$2"
            shift 2
            ;;
        --force-git-rebase)
            FORCE_GIT_REBASE=true
            shift
            ;;
        --backup-cron)
            SETUP_BACKUP_CRON=true
            shift
            ;;
        --help)
            echo "MyDatabase Deployment Script"
            echo ""
            echo "Usage: $0 [options]"
            echo ""
            echo "Options:"
            echo "  --php-fpm-service=NAME   PHP-FPM service name (default: php8.3-fpm)"
            echo "  --webuser=USER           Web server user (default: www-data)"
            echo "  --force-git-rebase       Force reset to remote branch (overwrites local changes)"
            echo "  --backup-cron            Setup daily backup cron job at 23:00"
            echo "  --help                   Show this help message"
            echo ""
            echo "Examples:"
            echo "  $0                                              # Use defaults"
            echo "  $0 --php-fpm-service=php8.2-fpm                # Use PHP 8.2"
            echo "  $0 --webuser=nginx                             # Use nginx user"
            echo "  $0 --force-git-rebase                          # Force reset to remote"
            echo "  $0 --backup-cron                               # Setup backup cron"
            echo "  $0 --php-fpm-service=php7.4-fpm --webuser=apache  # Custom PHP and user"
            echo ""
            echo "Current configuration:"
            echo "  PROJECT_DIR: $PROJECT_DIR"
            echo "  PHP_FPM_SERVICE: $PHP_FPM_SERVICE"
            echo "  WEBUSER: $WEBUSER"
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

echo "---------------------------"
echo "DÉBUT DU DÉPLOIEMENT"
echo "---------------------------"
echo "Configuration:"
echo "  PROJECT_DIR: $PROJECT_DIR"
echo "  PHP_FPM_SERVICE: $PHP_FPM_SERVICE"
echo "  WEBUSER: $WEBUSER"
echo "  FORCE_GIT_REBASE: $FORCE_GIT_REBASE"
echo "---------------------------"

cd "$PROJECT_DIR" || { echo "Dossier introuvable: $PROJECT_DIR"; exit 1; }

# Get current git branch
GIT_CURRENT_BRANCH=$(sudo -u $WEBUSER git rev-parse --abbrev-ref HEAD 2>/dev/null)
if [ -z "$GIT_CURRENT_BRANCH" ]; then
    echo "Erreur: Impossible de déterminer la branche Git actuelle"
    exit 1
fi
echo "Branche Git actuelle: $GIT_CURRENT_BRANCH"

echo "1. Pull du code depuis Gitlab..."
if [ "$FORCE_GIT_REBASE" = true ]; then
    echo "   Mode force-rebase activé: réinitialisation vers origin/$GIT_CURRENT_BRANCH"
    sudo -u $WEBUSER bash -lc "git fetch origin && git reset --hard origin/$GIT_CURRENT_BRANCH" || { echo "Échec du git reset --hard"; exit 1; }
else
    sudo -u $WEBUSER git pull || { echo "Échec du git pull"; exit 1; }
fi

echo "2. Installation des dépendances PHP (composer)..."
sudo -u $WEBUSER composer install --no-interaction --prefer-dist --optimize-autoloader || { echo "Échec du composer install"; exit 1; }

echo "3. Installation des dépendances JS (npm) et build..."
sudo -u $WEBUSER npm install --no-audit || { echo "Échec du npm install"; exit 1; }
sudo -u $WEBUSER npm run build || { echo "Échec du npm run build"; exit 1; }

echo "4. Migration de la base de données..."
sudo -u $WEBUSER php artisan migrate --force || { echo "Échec de la migration"; exit 1; }

echo "5. Nettoyage et cache Laravel..."
sudo -u $WEBUSER php artisan optimize:clear
sudo -u $WEBUSER php artisan optimize

echo "6. Permissions des dossiers storage et cache..."
sudo chown -R $WEBUSER:$WEBUSER storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

echo "7. Reload du service PHP-FPM..."
sudo systemctl reload $PHP_FPM_SERVICE

# Setup backup cron if requested
if [ "$SETUP_BACKUP_CRON" = true ]; then
    echo "8. Configuration du cron de backup..."
    
    # Get the application folder name from the project directory
    APP_FOLDER_NAME=$(basename "$PROJECT_DIR")
    CRON_FILE="/etc/cron.d/$APP_FOLDER_NAME-backup"
    BACKUP_SCRIPT="$PROJECT_DIR/scripts/daily-backup.sh"
    
    # Check if backup script exists
    if [ ! -f "$BACKUP_SCRIPT" ]; then
        echo "   Attention: Le script de backup n'existe pas: $BACKUP_SCRIPT"
        echo "   Création du cron annulée"
    else
        # Create the cron file
        echo "   Création du fichier cron: $CRON_FILE"
        
        # Write cron job to file (requires sudo)
        sudo tee "$CRON_FILE" > /dev/null <<EOF
# MyDatabase Daily Backup Cron Job
# Generated by deploy.sh on $(date '+%Y-%m-%d %H:%M:%S')
# Application: $PROJECT_DIR
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

# Daily backup at 23:00
0 23 * * * $WEBUSER /usr/bin/bash $BACKUP_SCRIPT
EOF
        
        # Set proper permissions for cron file
        sudo chmod 644 "$CRON_FILE"
        
        echo "   Cron de backup configuré avec succès:"
        echo "   - Fichier cron: $CRON_FILE"
        echo "   - Utilisateur: $WEBUSER"
        echo "   - Horaire: 23:00 tous les jours"
        echo "   - Script: $BACKUP_SCRIPT"
        
        # Reload cron service to apply changes
        if command -v systemctl &> /dev/null; then
            sudo systemctl reload cron 2>/dev/null || sudo systemctl reload crond 2>/dev/null || true
        fi
    fi
fi

echo "---------------------------"
echo "DÉPLOIEMENT TERMINÉ AVEC SUCCÈS !"
echo "---------------------------"
