const databaseName = 'facturation-clinique';
const storeName = 'factures-en-attente';

function openDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(databaseName, 1);

        request.onupgradeneeded = () => {
            request.result.createObjectStore(storeName, { keyPath: 'idempotency_key' });
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function withStore(mode, callback) {
    const database = await openDatabase();

    return new Promise((resolve, reject) => {
        const transaction = database.transaction(storeName, mode);
        const store = transaction.objectStore(storeName);
        const result = callback(store);

        transaction.oncomplete = () => {
            database.close();
            resolve(result?.result);
        };
        transaction.onerror = () => {
            database.close();
            reject(transaction.error);
        };
        transaction.onabort = () => {
            database.close();
            reject(transaction.error);
        };
    });
}

function listPending() {
    return withStore('readonly', (store) => store.getAll());
}

function savePending(invoice) {
    return withStore('readwrite', (store) => store.put(invoice));
}

function removePending(idempotencyKey) {
    return withStore('readwrite', (store) => store.delete(idempotencyKey));
}

function notifyPendingInvoicesChanged() {
    window.dispatchEvent(new Event('offline-invoices-updated'));
}

function setStatus(element, message, isError = false) {
    if (!element) {
        return;
    }

    element.textContent = message;
    element.hidden = !message;
    element.classList.toggle('clinic-alert--error', isError);
}

function responseMessage(response, body) {
    const validationMessage = body?.errors
        ? Object.values(body.errors).flat()[0]
        : null;

    return validationMessage || body?.message || `La synchronisation a échoué (HTTP ${response.status}).`;
}

function createInvoicePayload(form) {
    const patientName = form.elements.patient_nom.value.trim();
    const selectedLines = [...form.querySelectorAll('.chk:checked')].map((checkbox) => {
        const row = checkbox.closest('.clinic-check-row');
        const quantity = Number.parseInt(row.querySelector('.qte').value, 10) || 1;

        return {
            service_id: Number.parseInt(checkbox.value, 10),
            quantite: quantity,
            local_line: {
                designation: row.querySelector('.font-semibold').textContent.trim(),
                prix: Number.parseFloat(checkbox.dataset.prix),
                quantite: quantity,
            },
        };
    });

    if (!patientName) {
        throw new Error('Renseignez le nom du patient.');
    }

    if (selectedLines.length === 0) {
        throw new Error('Choisissez au moins une prestation.');
    }

    return {
        idempotency_key: crypto.randomUUID(),
        patient_nom: patientName,
        patient_telephone: form.elements.patient_telephone.value.trim() || null,
        catalog_proof: form.dataset.catalogProof,
        lignes: selectedLines.map(({ service_id, quantite }) => ({ service_id, quantite })),
        local_lines: selectedLines.map(({ local_line }) => local_line),
        user_id: form.dataset.userId,
        created_at: new Date().toISOString(),
    };
}

async function sendInvoice(url, csrfToken, invoice) {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify(invoice),
    });

    if (response.status === 419 || response.status >= 500) {
        throw new Error('La connexion au serveur a échoué.');
    }

    if (!response.headers.get('content-type')?.includes('application/json')) {
        throw new Error('La session doit être renouvelée avant la synchronisation.');
    }

    const body = await response.json();

    if (!response.ok) {
        const error = new Error(responseMessage(response, body));
        error.permanent = true;
        throw error;
    }

    return body;
}

export function startOfflineInvoiceSync() {
    const currentUserId = document.querySelector('meta[name="offline-user-id"]')?.content;
    const syncUrl = document.querySelector('meta[name="offline-sync-url"]')?.content;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const syncStatus = document.getElementById('offline-sync-status');
    const offlineInvoices = document.getElementById('offline-invoices');

    if (!currentUserId || !syncUrl || !csrfToken) {
        return;
    }

    if (!('indexedDB' in window)) {
        setStatus(
            syncStatus,
            'Ce navigateur ne permet pas de conserver des factures hors ligne. Utilisez un navigateur moderne.',
            true
        );
        return;
    }

    let syncing = false;

    const renderPendingInvoices = async () => {
        if (!offlineInvoices) {
            return;
        }

        const list = offlineInvoices.querySelector('[data-offline-invoices-list]');
        const status = offlineInvoices.querySelector('[data-offline-invoices-status]');
        const pending = (await listPending())
            .filter((invoice) => String(invoice.user_id) === String(currentUserId))
            .sort((first, second) => new Date(second.created_at) - new Date(first.created_at));

        list.replaceChildren();
        offlineInvoices.hidden = pending.length === 0;

        if (pending.length === 0) {
            return;
        }

        setStatus(
            status,
            navigator.onLine
                ? `${pending.length} facture(s) en attente de synchronisation.`
                : `${pending.length} facture(s) enregistrée(s) sur cet appareil. Vous êtes hors ligne.`
        );

        for (const invoice of pending) {
            const row = document.createElement('tr');
            const cells = [
                `Hors ligne · ${invoice.idempotency_key.slice(0, 8)}`,
                invoice.patient_nom,
                `${formatAmount(invoice.local_lines)} ${offlineInvoices.dataset.currency}`,
                formatDate(invoice.created_at),
                'Non synchronisée',
            ];

            for (const value of cells) {
                const cell = document.createElement('td');
                cell.textContent = value;
                row.append(cell);
            }

            const actions = document.createElement('td');
            const printButton = document.createElement('button');
            printButton.type = 'button';
            printButton.className = 'clinic-link';
            printButton.textContent = 'Imprimer';
            printButton.addEventListener('click', () => {
                try {
                    printOfflineInvoice(invoice, offlineInvoices.dataset);
                } catch (error) {
                    setStatus(status, error.message, true);
                }
            });
            actions.append(printButton);
            row.append(actions);
            list.append(row);
        }
    };

    const syncPendingInvoices = async () => {
        if (syncing) {
            return;
        }

        syncing = true;

        try {
            const pending = await listPending();
            const ownInvoices = pending.filter((invoice) => invoice.user_id === currentUserId);
            const otherUserInvoices = pending.length - ownInvoices.length;

            if (ownInvoices.length === 0) {
                setStatus(
                    syncStatus,
                    otherUserInvoices > 0
                        ? 'Des factures en attente sont associées à un autre compte. Connectez-vous avec ce compte pour les synchroniser.'
                        : ''
                );
                await renderPendingInvoices();
                return;
            }

            if (!navigator.onLine) {
                setStatus(syncStatus, `${ownInvoices.length} facture(s) enregistrée(s) sur cet appareil, en attente d’Internet.`);
                await renderPendingInvoices();
                return;
            }

            for (const invoice of ownInvoices) {
                try {
                    const result = await sendInvoice(syncUrl, csrfToken, invoice);
                    await removePending(invoice.idempotency_key);
                    notifyPendingInvoicesChanged();
                    setStatus(syncStatus, result.message || 'Facture synchronisée.');
                } catch (error) {
                    setStatus(
                        syncStatus,
                        `${error.message} La facture reste enregistrée sur cet appareil et sera réessayée.`,
                        error.permanent
                    );
                    break;
                }
            }

            const remaining = (await listPending()).filter((invoice) => invoice.user_id === currentUserId);
            if (remaining.length > 0 && navigator.onLine) {
                setStatus(syncStatus, `${remaining.length} facture(s) toujours en attente de synchronisation.`, true);
            }
            await renderPendingInvoices();
        } catch (error) {
            setStatus(syncStatus, `Impossible d’accéder aux factures en attente : ${error.message}`, true);
        } finally {
            syncing = false;
        }
    };

    document.querySelectorAll('[data-offline-invoice]').forEach((form) => {
        const formStatus = form.querySelector('[data-offline-form-status]');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            setStatus(formStatus, '');

            let invoice;
            try {
                invoice = createInvoicePayload(form);
            } catch (error) {
                setStatus(formStatus, error.message, true);
                return;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;

            if (navigator.onLine) {
                try {
                    const result = await sendInvoice(form.dataset.syncUrl, csrfToken, invoice);
                    window.location.assign(result.redirect_url);
                    return;
                } catch (error) {
                    if (error.permanent) {
                        setStatus(formStatus, error.message, true);
                        submitButton.disabled = false;
                        return;
                    }
                }
            }

            try {
                await savePending(invoice);
                notifyPendingInvoicesChanged();
                form.reset();
                document.getElementById('total').textContent = '0.00';
                setStatus(formStatus, 'Facture enregistrée sur cet appareil. Consultez le statut de synchronisation ci-dessus.');
                await syncPendingInvoices();
            } catch (error) {
                setStatus(formStatus, `La facture n’a pas pu être enregistrée sur cet appareil : ${error.message}`, true);
            } finally {
                submitButton.disabled = false;
            }
        });
    });

    window.addEventListener('online', syncPendingInvoices);
    window.addEventListener('offline', syncPendingInvoices);
    window.addEventListener('offline-invoices-updated', () => {
        renderPendingInvoices().catch((error) => {
            setStatus(
                offlineInvoices?.querySelector('[data-offline-invoices-status]'),
                `Impossible d’afficher les factures hors ligne : ${error.message}`,
                true
            );
        });
    });
    syncPendingInvoices();
}

function formatAmount(lines) {
    if (!Array.isArray(lines)) {
        return '—';
    }

    return lines.reduce((total, line) => total + Number(line.prix) * Number(line.quantite), 0).toFixed(2);
}

function formatDate(value) {
    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? 'Date inconnue'
        : new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'short' }).format(date);
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    })[character]);
}

function printOfflineInvoice(invoice, clinic) {
    if (!Array.isArray(invoice.local_lines) || invoice.local_lines.length === 0) {
        throw new Error('Les détails de cette facture ne sont pas disponibles pour l’impression hors ligne.');
    }

    const printWindow = window.open('', '_blank');

    if (!printWindow) {
        throw new Error('Autorisez les fenêtres contextuelles pour imprimer la facture.');
    }

    const date = escapeHtml(formatDate(invoice.created_at));

    printWindow.document.open();
    printWindow.document.write(`<!doctype html>
        <html lang="fr">
        <head>
            <meta charset="utf-8">
            <title>Facture hors ligne - ${escapeHtml(invoice.patient_nom)}</title>
            <style>
                @page { size: 80mm auto; margin: 4mm; }
                body { max-width: 72mm; margin: 0 auto; color: #252525; font: 10px/1.4 monospace; }
                h1 { margin: 0 0 8px; font: normal 18px Arial, sans-serif; text-align: center; }
                .clinic, .patient, .thanks { text-align: center; }
                .clinic { margin-bottom: 8px; }
                .notice { margin: 8px 0; padding: 6px; border: 1px dashed #b7791f; text-align: center; }
                .separator { margin: 7px 0; border-top: 1px dashed #555; }
                .line { display: flex; justify-content: space-between; gap: 5px; margin-top: 8px; }
                .line-name { overflow-wrap: anywhere; }
                .line-amount { white-space: nowrap; }
                .line-detail { color: #444; }
                .total { display: flex; justify-content: space-between; gap: 5px; margin-top: 8px; font-weight: bold; }
                .thanks { margin-top: 14px; font: 15px Arial, sans-serif; }
                .issued-by { margin-top: 10px; text-align: center; font-size: 9px; }
            </style>
        </head>
        <body onload="window.print()">
            <h1>REÇU</h1>
            <div class="clinic"><strong>${escapeHtml(clinic.clinicName)}</strong><br>${escapeHtml(clinic.clinicAddress)}</div>
            <div class="separator"></div>
            <div>N° HORS LIGNE · ${escapeHtml(invoice.idempotency_key.slice(0, 8))}</div>
            <div>Le ${date}</div>
            <p class="notice">Non synchronisée — à synchroniser avant émission définitive.</p>
            <p class="patient"><strong>${escapeHtml(invoice.patient_nom)}</strong>
                ${invoice.patient_telephone ? `<br>Téléphone : ${escapeHtml(invoice.patient_telephone)}` : ''}
            </p>
            <div class="separator"></div>
            ${invoice.local_lines.map((line) => `
                    <span class="line-name">${escapeHtml(line.designation)}</span>
                    <span class="line-amount">${escapeHtml((Number(line.prix) * Number(line.quantite)).toFixed(2))} ${escapeHtml(clinic.currency)}</span>
            `).join('')}
            <div class="separator"></div>
            <div class="total"><span>TOTAL :</span><span>${formatAmount(invoice.local_lines)} ${escapeHtml(clinic.currency)}</span></div>
            <div class="separator"></div>
            <p class="thanks">MERCI !</p>
            <div class="issued-by">${escapeHtml(clinic.clinicName)}</div>
        </body>
        </html>`);
    printWindow.document.close();
}
