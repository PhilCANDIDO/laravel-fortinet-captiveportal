#!/bin/bash

#############################################################
# Laravel Captive Portal - Production Deployment Script
# Idempotent script for Continuous Delivery deployments
#
# Usage:
#   ./deploy.sh [options]
#
# Options:
#   --php-fpm-service=NAME   PHP-FPM service name (default: php-fpm)
#   --webuser=USER           Web server user (default: nginx)
#   --branch=BRANCH          Git branch to deploy (default: main)
#   --force-git-reset        Force reset to remote branch (overwrites local changes)
#   --skip-npm               Skip npm install and build
#   --backup-cron            Setup daily backup cron job at 23:00
#   --dry-run                Show what would be done without executing
#   --help                   Show this help message
#
# Examples:
#   ./deploy.sh
#   ./deploy.sh --php-fpm-service=php8.3-fpm --webuser=www-data
#   ./deploy.sh --branch=main --force-git-reset
#   ./deploy.sh --dry-run
#############################################################

set -euo pipefail

# =============================================================================
# Configuration
# =============================================================================

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_DIR="$PROJECT_DIR/storage/logs"
LOG_FILE="$LOG_DIR/deploy-$(date '+%Y-%m-%d').log"
TIMESTAMP_FORMAT="+%Y-%m-%d %H:%M:%S"

# Default values
PHP_FPM_SERVICE="php-fpm"
WEBUSER="nginx"
GIT_BRANCH="main"
FORCE_GIT_RESET=false
SKIP_NPM=false
SETUP_BACKUP_CRON=false
DRY_RUN=false

# Colors for terminal output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# =============================================================================
# Functions
# =============================================================================

log() {
    local level="$1"
    local message="$2"
    local timestamp
    timestamp=$(date "$TIMESTAMP_FORMAT")

    # Log to file
    echo "[$timestamp] [$level] $message" >> "$LOG_FILE"

    # Display to terminal with colors
    case $level in
        INFO)
            echo -e "${BLUE}[INFO]${NC} $message"
            ;;
        SUCCESS)
            echo -e "${GREEN}[SUCCESS]${NC} $message"
            ;;
        WARNING)
            echo -e "${YELLOW}[WARNING]${NC} $message"
            ;;
        ERROR)
            echo -e "${RED}[ERROR]${NC} $message"
            ;;
        STEP)
            echo -e "${GREEN}▶${NC} $message"
            ;;
        *)
            echo "$message"
            ;;
    esac
}

log_separator() {
    local message="$1"
    log "INFO" "=============================================="
    log "INFO" "$message"
    log "INFO" "=============================================="
}

check_requirements() {
    log "STEP" "Checking requirements..."

    local missing_requirements=()

    # Check for required commands
    for cmd in php composer git; do
        if ! command -v $cmd &> /dev/null; then
            missing_requirements+=("$cmd")
        fi
    done

    # Check for npm only if not skipping
    if [ "$SKIP_NPM" = false ] && ! command -v npm &> /dev/null; then
        missing_requirements+=("npm")
    fi

    if [ ${#missing_requirements[@]} -gt 0 ]; then
        log "ERROR" "Missing required commands: ${missing_requirements[*]}"
        exit 1
    fi

    # Check if .env file exists
    if [ ! -f "$PROJECT_DIR/.env" ]; then
        log "ERROR" ".env file not found. Please configure the application first."
        exit 1
    fi

    log "SUCCESS" "All requirements satisfied"
}

ensure_directories() {
    log "STEP" "Ensuring required directories exist..."

    if [ "$DRY_RUN" = true ]; then
        log "INFO" "[DRY-RUN] Would create: storage/framework/{cache,sessions,testing,views} bootstrap/cache"
        return
    fi

    mkdir -p "$PROJECT_DIR/storage/framework/"{cache,sessions,testing,views}
    mkdir -p "$PROJECT_DIR/bootstrap/cache"
    mkdir -p "$LOG_DIR"

    log "SUCCESS" "Directories verified"
}

fix_permissions() {
    log "STEP" "Setting file permissions..."

    if [ "$DRY_RUN" = true ]; then
        log "INFO" "[DRY-RUN] Would set ownership to $WEBUSER:$WEBUSER on storage and bootstrap/cache"
        return
    fi

    # Set ownership
    chown -R "$WEBUSER:$WEBUSER" "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache"

    # Set directory permissions
    find "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache" -type d -exec chmod 775 {} \;

    # Set file permissions
    find "$PROJECT_DIR/storage" "$PROJECT_DIR/bootstrap/cache" -type f -exec chmod 664 {} \;

    log "SUCCESS" "Permissions set for user: $WEBUSER"
}

git_pull() {
    log "STEP" "Pulling latest code from Git (branch: $GIT_BRANCH)..."

    if [ "$DRY_RUN" = true ]; then
        if [ "$FORCE_GIT_RESET" = true ]; then
            log "INFO" "[DRY-RUN] Would force reset to origin/$GIT_BRANCH"
        else
            log "INFO" "[DRY-RUN] Would pull from origin/$GIT_BRANCH"
        fi
        return
    fi

    cd "$PROJECT_DIR"

    # Fetch latest from remote
    sudo -u "$WEBUSER" git fetch origin "$GIT_BRANCH"

    if [ "$FORCE_GIT_RESET" = true ]; then
        log "WARNING" "Force reset enabled - local changes will be overwritten"
        sudo -u "$WEBUSER" git reset --hard "origin/$GIT_BRANCH"
    else
        # Check for local changes
        if ! sudo -u "$WEBUSER" git diff --quiet HEAD 2>/dev/null; then
            log "WARNING" "Local changes detected. Use --force-git-reset to overwrite."
        fi
        sudo -u "$WEBUSER" git pull origin "$GIT_BRANCH"
    fi

    local current_commit
    current_commit=$(git rev-parse --short HEAD)
    log "SUCCESS" "Code updated to commit: $current_commit"
}

composer_install() {
    log "STEP" "Installing PHP dependencies (Composer)..."

    if [ "$DRY_RUN" = true ]; then
        log "INFO" "[DRY-RUN] Would run: composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev"
        return
    fi

    cd "$PROJECT_DIR"
    sudo -u "$WEBUSER" composer install \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-dev

    log "SUCCESS" "PHP dependencies installed"
}

npm_build() {
    if [ "$SKIP_NPM" = true ]; then
        log "INFO" "Skipping npm install and build (--skip-npm)"
        return
    fi

    log "STEP" "Installing JS dependencies and building assets..."

    if [ "$DRY_RUN" = true ]; then
        log "INFO" "[DRY-RUN] Would run: npm ci && npm run build"
        return
    fi

    cd "$PROJECT_DIR"

    # Use npm ci for reproducible builds if package-lock.json exists
    if [ -f "$PROJECT_DIR/package-lock.json" ]; then
        sudo -u "$WEBUSER" npm ci --no-audit
    else
        sudo -u "$WEBUSER" npm install --no-audit
    fi

    sudo -u "$WEBUSER" npm run build

    log "SUCCESS" "Frontend assets built"
}

run_migrations() {
    log "STEP" "Running database migrations..."

    if [ "$DRY_RUN" = true ]; then
        log "INFO" "[DRY-RUN] Would run: php artisan migrate --force"
        return
    fi

    cd "$PROJECT_DIR"
    sudo -u "$WEBUSER" php artisan migrate --force --no-interaction

    log "SUCCESS" "Database migrations completed"
}

optimize_application() {
    log "STEP" "Optimizing Laravel application..."

    if [ "$DRY_RUN" = true ]; then
        log "INFO" "[DRY-RUN] Would run Laravel optimization commands"
        return
    fi

    cd "$PROJECT_DIR"

    # Clear all caches first (idempotent)
    log "INFO" "Clearing caches..."
    sudo -u "$WEBUSER" php artisan optimize:clear --no-interaction

    # Cache configuration
    log "INFO" "Caching configuration..."
    sudo -u "$WEBUSER" php artisan config:cache --no-interaction

    # Cache routes
    log "INFO" "Caching routes..."
    sudo -u "$WEBUSER" php artisan route:cache --no-interaction

    # Cache views
    log "INFO" "Caching views..."
    sudo -u "$WEBUSER" php artisan view:cache --no-interaction

    # Cache events (Laravel 9+)
    log "INFO" "Caching events..."
    sudo -u "$WEBUSER" php artisan event:cache --no-interaction 2>/dev/null || true

    log "SUCCESS" "Application optimized"
}

restart_services() {
    log "STEP" "Restarting services..."

    if [ "$DRY_RUN" = true ]; then
        log "INFO" "[DRY-RUN] Would reload: $PHP_FPM_SERVICE"
        return
    fi

    # Reload PHP-FPM
    if systemctl is-active --quiet "$PHP_FPM_SERVICE"; then
        systemctl reload "$PHP_FPM_SERVICE"
        log "SUCCESS" "PHP-FPM service reloaded: $PHP_FPM_SERVICE"
    else
        log "WARNING" "PHP-FPM service not running: $PHP_FPM_SERVICE"
    fi

    # Restart Horizon if it's running
    if systemctl is-active --quiet laravel-horizon 2>/dev/null; then
        systemctl restart laravel-horizon
        log "SUCCESS" "Laravel Horizon restarted"
    fi

    # Restart queue workers if configured
    cd "$PROJECT_DIR"
    sudo -u "$WEBUSER" php artisan queue:restart 2>/dev/null || true
    log "INFO" "Queue restart signal sent"
}

setup_backup_cron() {
    if [ "$SETUP_BACKUP_CRON" = false ]; then
        return
    fi

    log "STEP" "Setting up backup cron job..."

    local app_name
    app_name=$(basename "$PROJECT_DIR")
    local cron_file="/etc/cron.d/${app_name}-backup"
    local backup_script="$PROJECT_DIR/scripts/daily-backup.sh"

    if [ "$DRY_RUN" = true ]; then
        log "INFO" "[DRY-RUN] Would create cron file: $cron_file"
        return
    fi

    if [ ! -f "$backup_script" ]; then
        log "WARNING" "Backup script not found: $backup_script"
        return
    fi

    cat > "$cron_file" <<EOF
# Laravel Captive Portal - Daily Backup
# Generated by deploy.sh on $(date "$TIMESTAMP_FORMAT")
SHELL=/bin/bash
PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin

# Daily backup at 23:00
0 23 * * * $WEBUSER /usr/bin/bash $backup_script >> $LOG_DIR/backup.log 2>&1
EOF

    chmod 644 "$cron_file"

    # Reload cron
    systemctl reload cron 2>/dev/null || systemctl reload crond 2>/dev/null || true

    log "SUCCESS" "Backup cron configured: daily at 23:00"
}

show_help() {
    cat <<EOF
Laravel Captive Portal - Production Deployment Script

Usage: $0 [options]

Options:
  --php-fpm-service=NAME   PHP-FPM service name (default: $PHP_FPM_SERVICE)
  --webuser=USER           Web server user (default: $WEBUSER)
  --branch=BRANCH          Git branch to deploy (default: $GIT_BRANCH)
  --force-git-reset        Force reset to remote branch (overwrites local changes)
  --skip-npm               Skip npm install and build
  --backup-cron            Setup daily backup cron job at 23:00
  --dry-run                Show what would be done without executing
  --help                   Show this help message

Examples:
  $0                                                    # Deploy with defaults
  $0 --branch=main --php-fpm-service=php8.3-fpm        # Custom branch and PHP
  $0 --force-git-reset                                  # Force reset local changes
  $0 --dry-run                                          # Preview deployment steps

Log files:
  Deployment logs: $LOG_DIR/deploy-YYYY-MM-DD.log
EOF
}

# =============================================================================
# Parse Arguments
# =============================================================================

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
        --branch=*)
            GIT_BRANCH="${1#*=}"
            shift
            ;;
        --branch)
            GIT_BRANCH="$2"
            shift 2
            ;;
        --force-git-reset)
            FORCE_GIT_RESET=true
            shift
            ;;
        --skip-npm)
            SKIP_NPM=true
            shift
            ;;
        --backup-cron)
            SETUP_BACKUP_CRON=true
            shift
            ;;
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --help|-h)
            show_help
            exit 0
            ;;
        *)
            echo "Unknown option: $1"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# =============================================================================
# Main Execution
# =============================================================================

main() {
    # Ensure log directory exists before logging
    mkdir -p "$LOG_DIR"

    log_separator "DEPLOYMENT STARTED"

    log "INFO" "Configuration:"
    log "INFO" "  Project:     $PROJECT_DIR"
    log "INFO" "  Branch:      $GIT_BRANCH"
    log "INFO" "  PHP-FPM:     $PHP_FPM_SERVICE"
    log "INFO" "  Web User:    $WEBUSER"
    log "INFO" "  Force Reset: $FORCE_GIT_RESET"
    log "INFO" "  Skip NPM:    $SKIP_NPM"
    log "INFO" "  Dry Run:     $DRY_RUN"
    log "INFO" "  Log File:    $LOG_FILE"

    if [ "$DRY_RUN" = true ]; then
        log "WARNING" "DRY-RUN MODE - No changes will be made"
    fi

    cd "$PROJECT_DIR" || { log "ERROR" "Project directory not found: $PROJECT_DIR"; exit 1; }

    # Execute deployment steps
    check_requirements
    ensure_directories
    fix_permissions
    git_pull
    composer_install
    npm_build
    run_migrations
    optimize_application
    fix_permissions  # Run again after all changes
    restart_services
    setup_backup_cron

    log_separator "DEPLOYMENT COMPLETED SUCCESSFULLY"

    # Show summary
    local commit_hash
    commit_hash=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
    log "SUCCESS" "Deployed commit: $commit_hash"
    log "SUCCESS" "Log file: $LOG_FILE"

    return 0
}

# Run main function
main "$@"
