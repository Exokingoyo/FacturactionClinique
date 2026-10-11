<x-app-layout>

            <section
                id="offline-invoices"
                class="clinic-page clinic-page--narrow"
                data-currency="{{ config('clinique.devise') }}"
                data-clinic-name="{{ config('clinique.nom') }}"
                data-clinic-address="{{ config('clinique.adresse') }}"
                hidden
                aria-labelledby="offline-invoices-title"
            >
                <div class="clinic-card clinic-card__body">
                    <div class="clinic-page__heading">
                        <div>
                            <p class="clinic-eyebrow">Enregistrées sur cet appareil</p>
                            <h2 id="offline-invoices-title" class="clinic-title">Factures non synchronisées</h2>
                            <p class="clinic-subtitle">Ces factures sont disponibles hors ligne et seront synchronisées dès le retour d’Internet.</p>
                        </div>
                    </div>
                    <div class="clinic-alert" data-offline-invoices-status role="status" aria-live="polite"></div>
                    <div class="clinic-table-wrap">
                        <table class="clinic-table">
                            <thead>
                                <tr>
                                    <th scope="col">Facture</th>
                                    <th scope="col">Patient</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Statut</th>
                                    <th scope="col"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody data-offline-invoices-list></tbody>
                        </table>
                    </div>
                </div>
            </section>




    <section class="clinic-page">
        <div class="clinic-page__heading">
            <div>
                <p class="clinic-eyebrow">Suivi et historique</p>
                <h1 class="clinic-title">Factures</h1>
                <p class="clinic-subtitle">{{ Auth::user()->isAdmin() ? 'Consultez et gérez les factures de toute la clinique.' : 'Consultez les factures que vous avez créées.' }}</p>
            </div>
            <a class="clinic-button" href="{{ route('factures.create') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                Nouvelle facture
            </a>
        </div>

        <div class="clinic-card clinic-table-wrap">
            <table class="clinic-table">
                <thead>
                    <tr>
                        <th scope="col">N° facture</th>
                        <th scope="col">Patient</th>
                        <th scope="col">Total</th>
                        <th scope="col">Créée par</th>
                        <th scope="col">Date</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($factures as $f)
                        <tr>
                            <td class="clinic-table__number">{{ $f->numero }}</td>
                            <td>{{ $f->patient_nom }}</td>
                            <td>{{ number_format($f->total, 2) }} {{ config('clinique.devise') }}</td>
                            <td>{{ $f->user->name }}</td>
                            <td>{{ $f->created_at->format('d/m/Y H:i') }}</td>
                            <td><a class="clinic-link" href="{{ route('factures.show', $f) }}">Détails <span aria-hidden="true">→</span></a></td>
                        </tr>
                    @empty
                        <tr><td class="clinic-empty" colspan="6">Aucune facture enregistrée. Créez votre première facture pour la retrouver ici.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="clinic-pagination">{{ $factures->links() }}</div>
    </section>
</x-app-layout>
