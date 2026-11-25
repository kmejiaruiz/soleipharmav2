import { router } from '@inertiajs/vue3';

let activeRequests = 0;
let isBlocked = false;
let shieldElement = null;

function getOrCreateShield() {
    if (!shieldElement) {
        shieldElement = document.getElementById('solei-nprogress-shield');
        if (!shieldElement) {
            shieldElement = document.createElement('div');
            shieldElement.id = 'solei-nprogress-shield';
            shieldElement.setAttribute('aria-hidden', 'true');
            const target = document.body || document.documentElement;
            if (target) {
                target.appendChild(shieldElement);
            }
        }
    }
    return shieldElement;
}

export function activateClickBlocker() {
    isBlocked = true;
    const shield = getOrCreateShield();
    if (shield) {
        shield.classList.add('active');
        shield.style.display = 'block';
    }
    document.documentElement.classList.add('nprogress-blocking-clicks');
}

export function deactivateClickBlocker() {
    isBlocked = false;
    const shield = getOrCreateShield();
    if (shield) {
        shield.classList.remove('active');
        shield.style.display = 'none';
    }
    document.documentElement.classList.remove('nprogress-blocking-clicks');
}

export function initClickBlocker() {
    // 1. Interceptor de eventos en fase de captura (capture phase):
    // Ningún clic, submit ni pulsación de botón llega a los elementos hijos.
    const blockEvent = (e) => {
        if (isBlocked) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            return false;
        }
    };

    const blockedEvents = [
        'click',
        'dblclick',
        'mousedown',
        'mouseup',
        'pointerdown',
        'pointerup',
        'touchstart',
        'touchend',
        'submit'
    ];

    blockedEvents.forEach((evt) => {
        window.addEventListener(evt, blockEvent, true);
    });

    window.addEventListener(
        'keydown',
        (e) => {
            if (isBlocked && (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar' || e.key === 'Escape')) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                return false;
            }
        },
        true
    );

    // 2. Interceptar ciclo de peticiones de Inertia
    router.on('start', () => {
        activeRequests++;
        activateClickBlocker();
    });

    router.on('finish', () => {
        activeRequests = Math.max(0, activeRequests - 1);
        if (activeRequests === 0) {
            setTimeout(() => {
                if (activeRequests === 0 && !document.getElementById('nprogress')) {
                    deactivateClickBlocker();
                }
            }, 60);
        }
    });

    router.on('cancel', () => {
        activeRequests = Math.max(0, activeRequests - 1);
        if (activeRequests === 0) {
            deactivateClickBlocker();
        }
    });

    router.on('error', () => {
        activeRequests = Math.max(0, activeRequests - 1);
        if (activeRequests === 0) {
            deactivateClickBlocker();
        }
    });

    // 3. MutationObserver para detectar cuando NProgress inserta o quita #nprogress del DOM
    const observer = new MutationObserver(() => {
        const hasNProgress = document.getElementById('nprogress') !== null;
        if (hasNProgress) {
            activateClickBlocker();
        } else if (activeRequests === 0 && isBlocked) {
            deactivateClickBlocker();
        }
    });

    const attach = () => {
        getOrCreateShield();
        observer.observe(document.documentElement, { childList: true, subtree: true });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attach);
    } else {
        attach();
    }

    // Exponer globalmente para pruebas rápidas en consola si se desea
    window.__soleiActivateShield = activateClickBlocker;
    window.__soleiDeactivateShield = deactivateClickBlocker;
    window.__soleiIsClickBlocked = () => isBlocked;
}
