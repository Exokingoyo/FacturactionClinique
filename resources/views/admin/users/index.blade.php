<x-app-layout>
    <section class="clinic-page">
        <div class="clinic-page__heading">
            <div>
                <p class="clinic-eyebrow">Administration</p>
                <h1 class="clinic-title">Utilisateurs</h1>
                <p class="clinic-subtitle">Gérez les comptes et les rôles de l’équipe.</p>
            </div>
            <a class="clinic-button" href="{{ route('admin.users.create') }}">+ Ajouter un utilisateur</a>
        </div>

        @if (session('ok'))
            <div class="clinic-alert" role="status">{{ session('ok') }}</div>
        @endif
        @if ($errors->any())
            <div class="clinic-alert clinic-alert--error" role="alert">{{ $errors->first() }}</div>
        @endif

        <div class="clinic-card clinic-table-wrap">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-end gap-3" style="padding:16px">
                <div style="flex:1 1 220px">
                    <label class="clinic-label" for="user-search">Rechercher</label>
                    <input id="user-search" class="clinic-field" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nom, adresse e-mail ou rôle">
                </div>
                <div style="flex:0 1 190px">
                    <label class="clinic-label" for="user-sort">Trier par</label>
                    <select id="user-sort" class="clinic-field" name="sort">
                        <option value="name" @selected($sort === 'name')>Nom</option>
                        <option value="email" @selected($sort === 'email')>Adresse e-mail</option>
                        <option value="role" @selected($sort === 'role')>Rôle</option>
                        <option value="is_active" @selected($sort === 'is_active')>État</option>
                        <option value="created_at" @selected($sort === 'created_at')>Date de création</option>
                    </select>
                </div>
                <div style="flex:0 1 150px">
                    <label class="clinic-label" for="user-direction">Ordre</label>
                    <select id="user-direction" class="clinic-field" name="direction">
                        <option value="asc" @selected($direction === 'asc')>Croissant</option>
                        <option value="desc" @selected($direction === 'desc')>Décroissant</option>
                    </select>
                </div>
                <button class="clinic-button" type="submit">Appliquer</button>
                @if (($filters['search'] ?? '') !== '')
                    <a class="clinic-button clinic-button--secondary" href="{{ route('admin.users.index', ['sort' => $sort, 'direction' => $direction]) }}">Effacer</a>
                @endif
            </form>
            <table class="clinic-table">
                <thead>
                    <tr><th scope="col">Nom</th><th scope="col">Adresse e-mail</th><th scope="col">Rôle</th><th scope="col">État</th><th scope="col">Créé le</th><th scope="col">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="clinic-table__number">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="clinic-role {{ $user->isAdmin() ? 'clinic-role--admin' : '' }}">
                                    {{ $user->isAdmin() ? 'Administrateur' : 'Équipe' }}
                                </span>
                            </td>
                            <td><span class="clinic-role {{ $user->is_active ? 'clinic-role--active' : 'clinic-role--inactive' }}">{{ $user->is_active ? 'Actif' : 'Désactivé' }}</span></td>
                            <td>{{ $user->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div class="clinic-table-actions">
                                    <a class="clinic-link" href="{{ route('admin.users.edit', $user) }}">Modifier</a>
                                    @unless (Auth::user()->is($user))
                                        <form method="POST" action="{{ route('admin.users.status', $user) }}" onsubmit="return confirm(@js(($user->is_active ? 'Désactiver le compte de ' : 'Réactiver le compte de ').$user->name.' ?'))">
                                            @csrf
                                            @method('PATCH')
                                            <button class="clinic-link {{ $user->is_active ? 'clinic-link--danger' : '' }}" type="submit">{{ $user->is_active ? 'Désactiver' : 'Réactiver' }}</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="clinic-empty" colspan="6">
                            {{ ($filters['search'] ?? '') !== '' ? 'Aucun compte ne correspond à cette recherche.' : 'Aucun compte utilisateur.' }}
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="clinic-pagination">{{ $users->links() }}</div>
    </section>
</x-app-layout>
