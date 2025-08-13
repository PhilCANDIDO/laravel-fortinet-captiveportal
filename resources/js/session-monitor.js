/**
 * Session Monitor - Warns users before session expires
 */
class SessionMonitor {
    constructor(options = {}) {
        this.timeout = options.timeout || 15 * 60 * 1000; // 15 minutes default
        this.warningTime = options.warningTime || 2 * 60 * 1000; // 2 minutes warning
        this.checkInterval = options.checkInterval || 30 * 1000; // Check every 30 seconds
        this.lastActivity = Date.now();
        this.warningShown = false;
        this.warningModal = null;
        this.countdownInterval = null;
        
        this.init();
    }

    init() {
        // Track user activity
        this.trackActivity();
        
        // Start monitoring
        this.startMonitoring();
        
        // Create warning modal
        this.createWarningModal();
    }

    trackActivity() {
        const events = ['mousedown', 'keydown', 'scroll', 'touchstart', 'click'];
        events.forEach(event => {
            document.addEventListener(event, () => {
                this.lastActivity = Date.now();
                this.hideWarning();
            });
        });
    }

    startMonitoring() {
        setInterval(() => {
            const now = Date.now();
            const timeSinceActivity = now - this.lastActivity;
            const timeUntilTimeout = this.timeout - timeSinceActivity;
            
            // Update session indicator
            this.updateSessionIndicator(timeUntilTimeout);
            
            // Check if we should show warning
            if (timeUntilTimeout <= this.warningTime && timeUntilTimeout > 0 && !this.warningShown) {
                this.showWarning(timeUntilTimeout);
            }
            
            // Check if session has expired
            if (timeUntilTimeout <= 0) {
                this.handleSessionExpired();
            }
        }, this.checkInterval);
    }

    updateSessionIndicator(timeRemaining) {
        const indicator = document.getElementById('session-indicator');
        if (!indicator) return;
        
        const minutes = Math.floor(timeRemaining / 60000);
        const seconds = Math.floor((timeRemaining % 60000) / 1000);
        
        if (timeRemaining > this.warningTime) {
            indicator.className = 'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800';
            indicator.innerHTML = `
                <svg class="w-2 h-2 mr-1 fill-current" viewBox="0 0 8 8">
                    <circle cx="4" cy="4" r="3"/>
                </svg>
                Session active
            `;
        } else if (timeRemaining > 0) {
            indicator.className = 'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800';
            indicator.innerHTML = `
                <svg class="w-2 h-2 mr-1 fill-current animate-pulse" viewBox="0 0 8 8">
                    <circle cx="4" cy="4" r="3"/>
                </svg>
                ${minutes}:${seconds.toString().padStart(2, '0')}
            `;
        } else {
            indicator.className = 'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800';
            indicator.innerHTML = `
                <svg class="w-2 h-2 mr-1 fill-current" viewBox="0 0 8 8">
                    <circle cx="4" cy="4" r="3"/>
                </svg>
                Session expired
            `;
        }
    }

    createWarningModal() {
        const modal = document.createElement('div');
        modal.id = 'session-warning-modal';
        modal.className = 'fixed inset-0 z-50 hidden overflow-y-auto';
        modal.innerHTML = `
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-sm sm:w-full sm:p-6">
                    <div>
                        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-yellow-100 rounded-full">
                            <svg class="w-6 h-6 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                ${this.getTranslation('session_warning_title', 'Session Expiring Soon')}
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    ${this.getTranslation('session_warning_message', 'Your session will expire in')} 
                                    <span id="countdown-timer" class="font-bold">2:00</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="button" id="extend-session-btn" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                            ${this.getTranslation('extend_session', 'Extend Session')}
                        </button>
                        <button type="button" id="logout-btn" class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                            ${this.getTranslation('logout', 'Logout')}
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        this.warningModal = modal;
        
        // Add event listeners
        document.getElementById('extend-session-btn').addEventListener('click', () => {
            this.extendSession();
        });
        
        document.getElementById('logout-btn').addEventListener('click', () => {
            this.logout();
        });
    }

    showWarning(timeRemaining) {
        this.warningShown = true;
        this.warningModal.classList.remove('hidden');
        
        // Start countdown
        this.startCountdown(timeRemaining);
    }

    hideWarning() {
        if (!this.warningShown) return;
        
        this.warningShown = false;
        this.warningModal.classList.add('hidden');
        
        // Stop countdown
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
            this.countdownInterval = null;
        }
    }

    startCountdown(timeRemaining) {
        const updateCountdown = () => {
            const now = Date.now();
            const remaining = this.timeout - (now - this.lastActivity);
            
            if (remaining <= 0) {
                clearInterval(this.countdownInterval);
                this.handleSessionExpired();
                return;
            }
            
            const minutes = Math.floor(remaining / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000);
            
            const timer = document.getElementById('countdown-timer');
            if (timer) {
                timer.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            }
        };
        
        updateCountdown();
        this.countdownInterval = setInterval(updateCountdown, 1000);
    }

    extendSession() {
        // Make AJAX request to extend session
        fetch('/admin/session/extend', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
            },
        }).then(() => {
            this.lastActivity = Date.now();
            this.hideWarning();
        });
    }

    logout() {
        window.location.href = '/admin/logout';
    }

    handleSessionExpired() {
        window.location.href = '/admin/login?expired=1';
    }

    getTranslation(key, defaultText) {
        // Get translation from global translations object if available
        if (window.translations && window.translations[key]) {
            return window.translations[key];
        }
        return defaultText;
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.sessionMonitor = new SessionMonitor();
    });
} else {
    window.sessionMonitor = new SessionMonitor();
}

export default SessionMonitor;