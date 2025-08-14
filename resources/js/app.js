import './bootstrap';
import Alpine from 'alpinejs';
import 'flowbite';

// Import session monitor for admin pages
if (document.querySelector('#session-indicator')) {
    import('./session-monitor').then(module => {
        window.sessionMonitor = new module.default({
            timeout: window.sessionTimeout || 15 * 60 * 1000,
            warningTime: 2 * 60 * 1000,
            checkInterval: 30 * 1000
        });
    });
}

window.Alpine = Alpine;
Alpine.start();
