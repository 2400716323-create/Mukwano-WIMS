
import jsQR from 'jsqr';
window.jsQR = jsQR;

const networkStatus = document.getElementById('network-status');
const installButton = document.getElementById('install-app');
const queuePanel = document.getElementById('offline-queue');
const queueSummary = document.getElementById('offline-queue-summary');
const queueItems = document.getElementById('offline-queue-items');
const userId = document.querySelector('.pwa-tools')?.dataset.userId ?? '';
const databaseName = 'mukwano-wims-offline';
const databaseVersion = 1;
const transactionStore = 'transactions';

let installPrompt;
let queueDatabase;
let syncing = false;

function updateNetworkStatus() {
    if (!networkStatus) return;

    const label = document.getElementById('network-status-label');

    if (label) {
        label.textContent = navigator.onLine ? 'System Online' : 'System Offline';
    } else {
        networkStatus.textContent = navigator.onLine
            ? 'System Online'
            : 'System Offline';
    }

    networkStatus.classList.toggle('offline', !navigator.onLine);
    networkStatus.classList.toggle('inactive', !navigator.onLine);
}

function openQueueDatabase() {
    if (queueDatabase) return Promise.resolve(queueDatabase);

    if (!('indexedDB' in window)) {
        return Promise.reject(
            new Error('IndexedDB is unavailable in this browser.')
        );
    }

    return new Promise((resolve, reject) => {
        const request = indexedDB.open(databaseName, databaseVersion);

        request.onupgradeneeded = () => {
            const database = request.result;

            if (!database.objectStoreNames.contains(transactionStore)) {
                database.createObjectStore(transactionStore, {
                    keyPath: 'clientId',
                });
            }
        };

        request.onsuccess = () => {
            queueDatabase = request.result;
            queueDatabase.onversionchange = () => queueDatabase.close();
            resolve(queueDatabase);
        };

        request.onerror = () => reject(
            request.error ?? new Error('The offline queue could not be opened.')
        );

        request.onblocked = () => reject(
            new Error('Close other WIMS tabs to upgrade the offline queue.')
        );
    });
}

async function saveTransaction(transaction) {
    const database = await openQueueDatabase();

    return new Promise((resolve, reject) => {
        const request = database
            .transaction(transactionStore, 'readwrite')
            .objectStore(transactionStore)
            .put(transaction);

        request.onsuccess = () => resolve();

        request.onerror = () => reject(
            request.error ?? new Error('The transaction could not be saved.')
        );
    });
}

async function getTransactions() {
    const database = await openQueueDatabase();

    return new Promise((resolve, reject) => {
        const request = database
            .transaction(transactionStore, 'readonly')
            .objectStore(transactionStore)
            .getAll();

        request.onsuccess = () => resolve(
            request.result.filter((entry) => entry.userId === userId)
        );

        request.onerror = () => reject(
            request.error ?? new Error('The offline queue could not be read.')
        );
    });
}

async function removeTransaction(clientId) {
    const database = await openQueueDatabase();

    return new Promise((resolve, reject) => {
        const request = database
            .transaction(transactionStore, 'readwrite')
            .objectStore(transactionStore)
            .delete(clientId);

        request.onsuccess = () => resolve();

        request.onerror = () => reject(
            request.error ?? new Error('The offline transaction could not be removed.')
        );
    });
}

async function updateTransaction(clientId, changes) {
    const database = await openQueueDatabase();

    return new Promise((resolve, reject) => {
        const transaction = database.transaction(
            transactionStore,
            'readwrite'
        );

        const store = transaction.objectStore(transactionStore);
        const request = store.get(clientId);

        request.onsuccess = () => {
            if (!request.result || request.result.userId !== userId) {
                reject(
                    new Error('The queued submission is not available for this account.')
                );
                return;
            }

            store.put({
                ...request.result,
                ...changes,
            });
        };

        transaction.oncomplete = () => resolve();

        transaction.onerror = () => reject(
            transaction.error ?? new Error('The offline transaction could not be updated.')
        );

        transaction.onabort = () => reject(
            transaction.error ?? new Error('The offline transaction update was cancelled.')
        );
    });
}

function formEntries(form) {
    return [...new FormData(form).entries()]
        .filter(([, value]) => typeof value === 'string')
        .map(([name, value]) => [name, value]);
}

function makeElement(tag, text, className = '') {
    const element = document.createElement(tag);
    element.textContent = text;

    if (className) {
        element.className = className;
    }

    return element;
}

async function renderQueue() {
    if (!queuePanel || !queueItems || !queueSummary) return;

    try {
        const transactions = (await getTransactions())
            .sort((a, b) => a.createdAt - b.createdAt);

        queueItems.replaceChildren();
        queuePanel.hidden = transactions.length === 0;

        if (transactions.length === 0) return;

        const counts = transactions.reduce((result, transaction) => {
            result[transaction.status] =
                (result[transaction.status] ?? 0) + 1;

            return result;
        }, {});

        queueSummary.textContent = transactions
            .map((item) =>
                `${item.operation}: ${item.status.replace('_', ' ')}`
            )
            .join(' · ');

        for (const transaction of transactions) {
            const item = document.createElement('li');

            item.append(makeElement(
                'strong',
                `${transaction.operation} — ${transaction.status.replace('_', ' ')}`
            ));

            const detail = transaction.error || (
                transaction.status === 'synced'
                    ? 'Server accepted this submission. Follow its next step before considering the work complete.'
                    : transaction.status === 'pending'
                        ? `Will retry when connected${transaction.attempts ? ` (attempt ${transaction.attempts})` : ''}.`
                        : 'Compare with the current server record; this submission was not applied again.'
            );

            item.append(makeElement('p', detail));

            if (transaction.status === 'synced' && transaction.nextUrl) {
                const link = makeElement(
                    'a',
                    'Open server-validated next step'
                );

                link.href = transaction.nextUrl;
                item.append(link);
            } else if (
                transaction.status === 'rejected'
                || transaction.status === 'needs_attention'
            ) {
                const reloadLink = makeElement(
                    'a',
                    'Reload the live form and resolve manually'
                );

                reloadLink.href = transaction.sourceUrl;
                item.append(reloadLink);
            }

            const removeButton = makeElement(
                'button',
                'Remove from this device',
                'secondary'
            );

            removeButton.type = 'button';

            removeButton.addEventListener('click', async () => {
                const warning = transaction.status === 'pending'
                    ? ' If the server received it before the connection failed, removing it will not undo that change; check the server first.'
                    : ' This does not change server data.';

                if (!window.confirm(
                    `Remove this saved submission from this device?${warning}`
                )) return;

                await removeTransaction(transaction.clientId);
                await renderQueue();
            });

            item.append(removeButton);
            queueItems.append(item);
        }

        const pending = counts.pending ?? 0;

        if (pending > 0 && navigator.onLine) {
            queueSummary.textContent +=
                ' · Retrying pending submissions automatically.';
        }
    } catch (error) {
        queuePanel.hidden = false;
        queueSummary.textContent = error.message;
    }
}

async function syncTransaction(transaction) {
    if (
        !navigator.onLine
        || transaction.status !== 'pending'
        || transaction.nextAttemptAt > Date.now()
    ) return;

    try {
        const body = new URLSearchParams(transaction.entries);

        const response = await fetch(transaction.syncUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body,
        });

        const result = await response.json().catch(() => ({}));

        if (response.ok && result.status === 'synced') {
            await updateTransaction(transaction.clientId, {
                status: 'synced',
                nextUrl: result.next_url,
                error: result.message ?? '',
                completedAt: Date.now(),
            });
        } else if (response.status === 422) {
            await updateTransaction(transaction.clientId, {
                status: 'rejected',
                error: Object.values(result.errors ?? {}).flat().join(' ')
                    || result.message
                    || 'The server rejected this form.',
            });
        } else if ([401, 403, 409, 419].includes(response.status)) {
            await updateTransaction(transaction.clientId, {
                status: 'needs_attention',
                error: result.message
                    || `The server returned ${response.status}. Sign in again or compare the current server record before retrying.`,
            });
        } else {
            throw new Error(
                result.message
                || `The server returned ${response.status}; this submission will be retried.`
            );
        }
    } catch (error) {
        const attempts = (transaction.attempts ?? 0) + 1;

        const delay = Math.min(
            60_000,
            2_000 * (2 ** Math.min(attempts - 1, 5))
        );

        await updateTransaction(transaction.clientId, {
            attempts,
            nextAttemptAt: Date.now() + delay,
            error: error.message || 'Network unavailable; retry scheduled.',
        });
    }
}

async function syncPendingTransactions() {
    if (syncing || !navigator.onLine) return;

    syncing = true;

    try {
        const transactions = await getTransactions();

        for (const transaction of transactions) {
            await syncTransaction(transaction);
        }
    } finally {
        syncing = false;
        await renderQueue();
    }
}

// Offline form submission handler
document.querySelectorAll('form[data-offline-operation]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        // Save Draft must submit directly to its own server route.
        const submitButton = event.submitter
            ?? form.querySelector('button[type="submit"], button:not([type])');

        if (submitButton?.dataset.skipOfflineQueue === 'true') {
            return;
        }

        // Preserve normal form submission when IndexedDB is unavailable.
        if (!('indexedDB' in window) && navigator.onLine) return;

        event.preventDefault();

        if (submitButton?.disabled) return;
        if (!form.reportValidity()) return;

        try {
            const entries = formEntries(form);

            const clientId = entries.find(
                ([name]) => name === 'client_id'
            )?.[1];

            if (!clientId) {
                throw new Error(
                    'This form has no unique client transaction ID. Reload it before submitting.'
                );
            }

            const transaction = {
                clientId,
                userId,
                operation: form.dataset.offlineOperation,
                syncUrl: form.dataset.offlineSyncUrl,
                sourceUrl: window.location.href,
                entries,
                status: 'pending',
                attempts: 0,
                nextAttemptAt: 0,
                createdAt: Date.now(),
                error: '',
            };

            await saveTransaction(transaction);

            if (submitButton) {
                submitButton.disabled = true;
            }

            await renderQueue();

            if (navigator.onLine) {
                await syncPendingTransactions();

                // Automatically open the review page only after
                // a Goods Receipt review has successfully synced.
                if (
                    form.dataset.offlineOperation === 'Goods receipt review'
                ) {
                    const savedTransactions = await getTransactions();

                    const syncedReceipt = savedTransactions.find(
                        (item) => item.clientId === clientId
                    );

                    if (
                        syncedReceipt?.status === 'synced'
                        && syncedReceipt.nextUrl
                    ) {
                        window.location.assign(syncedReceipt.nextUrl);
                        return;
                    }

                    if (
                        syncedReceipt?.status === 'rejected'
                        || syncedReceipt?.status === 'needs_attention'
                    ) {
                        window.alert(
                            syncedReceipt.error
                            || 'The server could not validate this receipt. Please check the form.'
                        );

                        if (submitButton) {
                            submitButton.disabled = false;
                        }
                    }
                }
            }
        } catch (error) {
            window.alert(`The submission was not saved: ${error.message}`);

            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    });
});

updateNetworkStatus();

window.addEventListener('online', () => {
    updateNetworkStatus();
    syncPendingTransactions();
});

window.addEventListener('offline', updateNetworkStatus);
window.addEventListener('focus', syncPendingTransactions);

window.setInterval(syncPendingTransactions, 5_000);

renderQueue();

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;

    document.querySelectorAll('#install-app, .install-app-trigger')
        .forEach((btn) => {
            btn.hidden = false;
        });
});

window.addEventListener('appinstalled', () => {
    installPrompt = null;

    document.querySelectorAll('#install-app, .install-app-trigger')
        .forEach((btn) => {
            btn.hidden = true;
        });
});

document.querySelectorAll('#install-app, .install-app-trigger').forEach((btn) => {
    btn.addEventListener('click', async () => {
        if (!installPrompt) return;

        installPrompt.prompt();
        await installPrompt.userChoice;

        installPrompt = null;

        document.querySelectorAll('#install-app, .install-app-trigger')
            .forEach((button) => {
                button.hidden = true;
            });
    });
});

if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js').catch((error) => {
            console.error(
                'WIMS service worker registration failed:',
                error
            );
        });
    });
}
