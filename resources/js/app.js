import './bootstrap';

import Alpine from 'alpinejs';
import { startOfflineInvoiceSync } from './offline-invoices';

window.Alpine = Alpine;

Alpine.start();

startOfflineInvoiceSync();
