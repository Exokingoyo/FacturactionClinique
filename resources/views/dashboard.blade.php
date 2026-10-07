<x-app-layout>
    <section class="clinic-page clinic-page--narrow">
        <div class="clinic-page__heading">
            <div>
                <p class="clinic-eyebrow">Espace de travail</p>
                <h1 class="clinic-title">Bonjour {{ Auth::user()->name }}</h1>
                <p class="clinic-subtitle">Accédez à vos factures et commencez une nouvelle consultation.</p>
            </div>
            <a class="clinic-button" href="{{ route('factures.create') }}">+ Nouvelle facture</a>
        </div>

        <div class="clinic-stat-grid clinic-stat-grid--staff">
            <a href="{{ route('factures.create') }}" class="clinic-card clinic-stat">
                <p class="clinic-stat__label">Facturation</p>
                <p class="clinic-stat__value">Nouvelle facture</p>
                <p class="clinic-subtitle">Enregistrer une consultation et ses prestations.</p>
            </a>
            <a href="{{ route('factures.index') }}" class="clinic-card clinic-stat">
                <p class="clinic-stat__label">Historique personnel</p>
                <p class="clinic-stat__value">Mes factures</p>
                <p class="clinic-subtitle">Consulter les factures que vous avez créées.</p>
            </a>
        </div>
    </section>
</x-app-layout>
