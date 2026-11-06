(function () {
    'use strict';

    var page = document.body;
    if (!page || !page.dataset.siteVersionUrl) {
        return;
    }

    var version = Number(page.dataset.siteVersion);
    var reloading = false;

    function checkForUpdate() {
        if (reloading || document.hidden) {
            return;
        }
        fetch(page.dataset.siteVersionUrl, { credentials: 'same-origin', cache: 'no-store' })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Version check failed');
                }
                return response.json();
            })
            .then(function (data) {
                if (Number.isSafeInteger(data.version) && data.version > version) {
                    reloading = true;
                    try {
                        sessionStorage.setItem('annaSkipSplashOnce', '1');
                    } catch (error) {
                        // The page can still refresh when storage is unavailable.
                    }
                    window.location.reload();
                }
            })
            .catch(function () {
                // Retry on the next interval if the connection is temporarily unavailable.
            });
    }

    window.setInterval(checkForUpdate, 15000);
    document.addEventListener('visibilitychange', checkForUpdate);
    window.addEventListener('focus', checkForUpdate);
}());
