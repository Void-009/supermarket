import './bootstrap';

var swRegistration = null;
var swRegistrationFailed = false;
var isSubscribed = false;

var BASE_URL = window.__APP_URL || '';

document.addEventListener('DOMContentLoaded', function () {
    if ('serviceWorker' in navigator && 'PushManager' in window) {
        registerServiceWorker();
    }
});

/**
 * iOS only exposes Web Push from 16.4 onwards, and even then only once the
 * site has been added to the Home Screen and launched from that icon. In a
 * normal Safari tab `window.Notification` is simply absent, so the generic
 * "not supported" message was both true and useless. Detect the two cases and
 * tell the user the one action that actually fixes it.
 */
function isIos() {
    return (
        /iPad|iPhone|iPod/.test(navigator.userAgent) ||
        (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)
    );
}

function isStandalone() {
    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true
    );
}

function iosInstallHint() {
    if (!isIos()) return null;
    if (isStandalone()) {
        return 'Notifications need iOS 16.4 or newer. Please update your iPhone, then reopen this app from your Home Screen.';
    }
    return 'On iPhone, notifications only work after you add this site to your Home Screen. Tap the Share button, choose "Add to Home Screen", then open M-Delight from your Home Screen and tap this button again.';
}

function registerServiceWorker() {
    navigator.serviceWorker
        .register(BASE_URL + '/sw.js')
        .then(function (registration) {
            swRegistration = registration;
            swRegistrationFailed = false;
            registration.addEventListener('updatefound', function () {
                if (registration.installing) {
                    registration.installing.addEventListener('statechange', function () {
                        if (this.state === 'activated') {
                            window.dispatchEvent(new CustomEvent('sw-ready', { detail: { registration: registration } }));
                        }
                    });
                }
            });
            window.dispatchEvent(new CustomEvent('sw-ready', { detail: { registration: registration } }));
        })
        .catch(function (error) {
            swRegistrationFailed = true;
            console.warn('Service worker registration failed:', error);
        });
}

function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var rawData = window.atob(base64);
    var output = new Uint8Array(rawData.length);
    for (var i = 0; i < rawData.length; ++i) {
        output[i] = rawData.charCodeAt(i);
    }
    return output;
}

window.subscribeToPush = function () {
    if (!swRegistration) {
        return Promise.reject(new Error(swRegistrationFailed
            ? 'Could not start the notification service on this browser. If you opened this page inside another app (WhatsApp, Facebook, Instagram), open it in Chrome or Safari instead.'
            : 'Service worker not registered yet. Please refresh the page and try again.'));
    }

    if (!('PushManager' in window)) {
        return Promise.reject(new Error('This browser does not support push notifications.'));
    }

    return swRegistration.pushManager
        .subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(window.VAPID_PUBLIC_KEY),
        })
        .then(function (subscription) {
            var subData = subscription.toJSON();
            return fetch(BASE_URL + '/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    endpoint: subData.endpoint,
                    auth_key: subData.keys.auth,
                    p256dh_key: subData.keys.p256dh,
                }),
            }).then(function (response) {
                // Previously a non-2xx response still resolved, so the UI
                // cheerfully reported "Notifications enabled!" while the
                // server had never stored the subscription and no push could
                // ever arrive. Reject instead so the failure is visible.
                if (!response.ok) {
                    return response.text().then(function (body) {
                        throw new Error('The server rejected the subscription (HTTP ' + response.status + '). ' + body.slice(0, 200));
                    });
                }
                return response;
            });
        })
        .then(function () {
            isSubscribed = true;
            window.dispatchEvent(new CustomEvent('push-subscription-changed', { detail: { subscribed: true } }));
            return { success: true };
        });
};

window.unsubscribeFromPush = function () {
    if (!swRegistration) {
        return Promise.reject(new Error('Service worker not registered'));
    }

    return swRegistration.pushManager
        .getSubscription()
        .then(function (subscription) {
            if (!subscription) {
                isSubscribed = false;
                window.dispatchEvent(new CustomEvent('push-subscription-changed', { detail: { subscribed: false } }));
                return { success: true };
            }
            return subscription.unsubscribe().then(function () {
                return fetch(BASE_URL + '/push/unsubscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        endpoint: subscription.toJSON().endpoint,
                    }),
                });
            });
        })
        .then(function (response) {
            if (response && response.ok) {
                isSubscribed = false;
                window.dispatchEvent(new CustomEvent('push-subscription-changed', { detail: { subscribed: false } }));
            }
            return response ? response.json() : { success: true };
        });
};

window.getPushSubscriptionStatus = function () {
    if (!swRegistration) {
        return Promise.resolve(false);
    }
    return swRegistration.pushManager.getSubscription().then(function (subscription) {
        isSubscribed = !!subscription;
        return isSubscribed;
    });
};

/**
 * Shared Alpine component for the enable/disable notifications control.
 *
 * Lives here rather than in a layout because both the storefront and the admin
 * panel need it. This module is listed in @vite ahead of the deferred
 * alpine.min.js, so the component is registered before Alpine boots.
 */
window.pushNotifyBtn = function () {
    return {
        subscribed: false,
        checking: true,

        init() {
            var self = this;
            this.checkStatus();
            window.addEventListener('push-subscription-changed', function (e) {
                self.subscribed = e.detail.subscribed;
            });
            window.addEventListener('sw-ready', function () {
                self.checkStatus();
            });
        },

        checkStatus() {
            var self = this;
            if (typeof window.getPushSubscriptionStatus === 'function') {
                window.getPushSubscriptionStatus().then(function (status) {
                    self.subscribed = status;
                    self.checking = false;
                });
            } else {
                this.checking = false;
            }
        },

        togglePushNotifications() {
            var self = this;
            if (this.subscribed) {
                if (typeof window.unsubscribeFromPush === 'function') {
                    window.unsubscribeFromPush().then(function () {
                        self.subscribed = false;
                        if (typeof showToast === 'function') {
                            showToast('Notifications disabled', 'info');
                        }
                    }).catch(function (e) {
                        if (typeof showToast === 'function') {
                            showToast(e.message || 'Could not turn notifications off', 'error');
                        }
                    });
                }
                return;
            }

            // iOS is the one platform where "unsupported" is a setup problem
            // rather than a hardware one, so give the fix instead of an error.
            if (!('Notification' in window)) {
                if (typeof showToast === 'function') {
                    var hint = iosInstallHint();
                    showToast(hint || 'Push notifications are not supported in this browser. Try Chrome on Android, or add the site to your Home Screen on iPhone.', 'error');
                }
                return;
            }

            if (Notification.permission === 'denied') {
                if (typeof showToast === 'function') {
                    showToast('Notifications are blocked for this site. Open your browser settings and allow notifications for this page, then try again.', 'error');
                }
                return;
            }

            Notification.requestPermission().then(function (permission) {
                if (permission !== 'granted') {
                    if (typeof showToast === 'function') {
                        showToast('Notification permission was declined, so notifications stay off.', 'error');
                    }
                    return;
                }

                // This branch used to be skipped silently when the helper was
                // missing, which is why some phones appeared to do nothing.
                if (typeof window.subscribeToPush !== 'function') {
                    if (typeof showToast === 'function') {
                        showToast('The notification script did not load. Please refresh the page and try again.', 'error');
                    }
                    return;
                }

                window.subscribeToPush().then(function () {
                    self.subscribed = true;
                    if (typeof showToast === 'function') {
                        showToast('Notifications enabled!', 'success');
                    }
                }).catch(function (e) {
                    if (typeof showToast === 'function') {
                        showToast((e && e.message) || 'Failed to enable notifications', 'error');
                    }
                });
            });
        },
    };
};
