<x-app-layout>
    <section class="clinic-page">
        <div class="clinic-page__heading">
            <div>
                <p class="clinic-eyebrow">Administration</p>
                <h1 class="clinic-title">Tableau de bord</h1>
                <p class="clinic-subtitle">Vue d’ensemble de l’activité et accès à la gestion de la clinique.</p>
            </div>
            <a class="clinic-button" href="{{ route('admin.users.create') }}">+ Ajouter un utilisateur</a>
        </div>

        <div class="clinic-stat-grid">
            <article class="clinic-card clinic-stat">
                <p class="clinic-stat__label">Comptes de l’équipe</p>
                <p class="clinic-stat__value">{{ $userCount }}</p>
            </article>
            <article class="clinic-card clinic-stat">
                <p class="clinic-stat__label">Factures émises</p>
                <p class="clinic-stat__value">{{ $invoiceCount }}</p>
            </article>
            <article class="clinic-card clinic-stat">
                <p class="clinic-stat__label">Total facturé ce mois</p>
                <p class="clinic-stat__value">{{ number_format($monthlyTotal, 2) }} {{ config('clinique.devise') }}</p>
            </article>
        </div>

        <div class="clinic-admin-shortcuts">
            <a class="clinic-card clinic-admin-shortcut" href="{{ route('admin.users.index') }}">
                <span class="clinic-admin-shortcut__icon" aria-hidden="true">♙</span>
                <span><strong>Gérer les utilisateurs</strong><small>Créer, modifier ou désactiver un compte.</small></span>
                <span aria-hidden="true">→</span>
            </a>
            <a class="clinic-card clinic-admin-shortcut" href="{{ route('admin.services.index') }}">
                <span class="clinic-admin-shortcut__icon" aria-hidden="true">＋</span>
                <span><strong>Gérer les services</strong><small>Administrer les prestations et les tarifs.</small></span>
                <span aria-hidden="true">→</span>
            </a>
        </div>

        <section class="clinic-card clinic-admin-recent">
            <div class="clinic-admin-section-heading">
                <div>
                    <h2 class="clinic-section-title">Activité récente</h2>
                    <p class="clinic-subtitle">Les dernières factures de tous les utilisateurs.</p>
                </div>
                <a class="clinic-link" href="{{ route('factures.index') }}">Toutes les factures →</a>
            </div>
            <div class="clinic-table-wrap clinic-table-wrap--bare">
                <table class="clinic-table">
                    <thead>
                        <tr><th scope="col">N° facture</th><th scope="col">Patient</th><th scope="col">Créée par</th><th scope="col">Date</th><th scope="col">Total</th><th scope="col"></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($recentFactures as $facture)
                            <tr>
                                <td class="clinic-table__number">{{ $facture->numero }}</td>
                                <td>{{ $facture->patient_nom }}</td>
                                <td>{{ $facture->user->name }}</td>
                                <td>{{ $facture->created_at->format('d/m/Y') }}</td>
                                <td>{{ number_format($facture->total, 2) }} {{ config('clinique.devise') }}</td>
                                <td><a class="clinic-link" href="{{ route('factures.show', $facture) }}">Voir →</a></td>
                            </tr>
                        @empty
                            <tr><td class="clinic-empty" colspan="6">Aucune facture n’a encore été créée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</x-app-layout>
