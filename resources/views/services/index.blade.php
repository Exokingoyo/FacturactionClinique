<x-app-layout>
    <section class="clinic-page">
        <div class="clinic-page__heading">
            <div>
                <p class="clinic-eyebrow">Catalogue</p>
                <h1 class="clinic-title">Actes et services</h1>
                <p class="clinic-subtitle">Gérez les prestations proposées et leurs tarifs.</p>
            </div>
        </div>

        @if (session('ok'))
            <div class="clinic-alert" role="status">{{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div class="clinic-alert clinic-alert--error" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="clinic-card clinic-card__body" style="margin-bottom:20px">
            <h2 class="text-lg font-bold" style="margin:0 0 16px">Ajouter un service</h2>
            <form method="POST" action="{{ route('admin.services.store') }}" class="clinic-form-grid">
                @csrf
                <div>
                    <label class="clinic-label" for="service-name">Nom du service</label>
                    <input id="service-name" name="nom" value="{{ old('nom') }}" placeholder="Ex. Consultation" class="clinic-field" required>
                </div>
                <div>
                    <label class="clinic-label" for="service-price">Tarif ({{ config('clinique.devise') }})</label>
                    <div class="flex gap-2">
                        <input id="service-price" name="prix" type="number" step="0.01" min="0" value="{{ old('prix') }}" placeholder="0,00" class="clinic-field" required>
                        <button class="clinic-button" type="submit">Ajouter</button>
                    </div>
                </div>
            </form>
        </div>

        <section class="clinic-card clinic-card__body" aria-labelledby="service-list-title">
            <div class="flex items-center justify-between gap-3" style="margin-bottom:8px">
                <h2 id="service-list-title" class="text-lg font-bold" style="margin:0">Services enregistrés</h2>
                <span class="text-sm text-gray-500">{{ $services->total() }} service(s)</span>
            </div>

            <form method="GET" action="{{ route('admin.services.index') }}" class="flex flex-wrap items-end gap-3" style="margin:16px 0">
                <div style="flex:1 1 220px">
                    <label class="clinic-label" for="service-search">Rechercher un service</label>
                    <input id="service-search" class="clinic-field" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nom du service">
                </div>
                <div style="flex:0 1 190px">
                    <label class="clinic-label" for="service-sort">Trier par</label>
                    <select id="service-sort" class="clinic-field" name="sort">
                        <option value="nom" @selected($sort === 'nom')>Nom</option>
                        <option value="prix" @selected($sort === 'prix')>Tarif</option>
                    </select>
                </div>
                <div style="flex:0 1 150px">
                    <label class="clinic-label" for="service-direction">Ordre</label>
                    <select id="service-direction" class="clinic-field" name="direction">
                        <option value="asc" @selected($direction === 'asc')>Croissant</option>
                        <option value="desc" @selected($direction === 'desc')>Décroissant</option>
                    </select>
                </div>
                <button class="clinic-button" type="submit">Appliquer</button>
                @if (($filters['search'] ?? '') !== '')
                    <a class="clinic-button clinic-button--secondary" href="{{ route('admin.services.index', ['sort' => $sort, 'direction' => $direction]) }}">Effacer</a>
                @endif
            </form>

            @forelse ($services as $s)
                <div class="clinic-service-row">
                    <form id="service-update-{{ $s->id }}" method="POST" action="{{ route('admin.services.update', $s) }}" class="contents">
                        @csrf
                        @method('PUT')
                        <label class="sr-only" for="service-name-{{ $s->id }}">Nom du service</label>
                        <input id="service-name-{{ $s->id }}" name="nom" value="{{ $s->nom }}" class="clinic-field" required>
                        <label class="sr-only" for="service-price-{{ $s->id }}">Tarif</label>
                        <input id="service-price-{{ $s->id }}" name="prix" type="number" step="0.01" min="0" value="{{ $s->prix }}" class="clinic-field" required>
                        <button class="clinic-button clinic-button--secondary" type="submit">Enregistrer</button>
                    </form>
                    <form method="POST" action="{{ route('admin.services.destroy', $s) }}" onsubmit="return confirm('Supprimer ce service ?')">
                        @csrf
                        @method('DELETE')
                        <button class="clinic-button clinic-button--danger" type="submit">Supprimer</button>
                    </form>
                </div>
            @empty
                <p class="clinic-empty">
                    {{ ($filters['search'] ?? '') !== '' ? 'Aucun service ne correspond à cette recherche.' : 'Aucun service pour le moment. Ajoutez-en un ci-dessus pour commencer.' }}
                </p>
            @endforelse
            <div class="clinic-pagination">{{ $services->links() }}</div>
        </section>
    </section>
</x-app-layout>
