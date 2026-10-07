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
                        <tr><td class="clinic-empty" colspan="6">Aucun compte utilisateur.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="clinic-pagination">{{ $users->links() }}</div>
    </section>
</x-app-layout>
