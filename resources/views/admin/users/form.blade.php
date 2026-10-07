<x-app-layout>
    <section class="clinic-page clinic-page--narrow">
        <div class="clinic-page__heading">
            <div>
                <p class="clinic-eyebrow">Administration · Utilisateurs</p>
                <h1 class="clinic-title">{{ $user->exists ? 'Modifier un compte' : 'Créer un compte' }}</h1>
                <p class="clinic-subtitle">Les comptes sont créés par un administrateur de la clinique.</p>
            </div>
            <a class="clinic-button clinic-button--secondary" href="{{ route('admin.users.index') }}">← Utilisateurs</a>
        </div>

        @if ($errors->any())
            <div class="clinic-alert clinic-alert--error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form class="clinic-card clinic-card__body" method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
            @csrf
            @if ($user->exists)
                @method('PUT')
            @endif

            <div class="clinic-form-grid">
                <div>
                    <label class="clinic-label" for="user-name">Nom complet</label>
                    <input class="clinic-field" id="user-name" name="name" value="{{ old('name', $user->name) }}" autocomplete="name" required>
                </div>
                <div>
                    <label class="clinic-label" for="user-email">Adresse e-mail</label>
                    <input class="clinic-field" id="user-email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" required>
                </div>
                <div>
                    <label class="clinic-label" for="user-role">Rôle</label>
                    <select class="clinic-field" id="user-role" name="role" required>
                        <option value="staff" @selected(old('role', $user->role ?: 'staff') === 'staff')>Membre de l’équipe</option>
                        <option value="admin" @selected(old('role', $user->role) === 'admin')>Administrateur</option>
                    </select>
                </div>
                <div>
                    <label class="clinic-label" for="user-password">Mot de passe {{ $user->exists ? '(laisser vide pour conserver l’actuel)' : '' }}</label>
                    <input class="clinic-field" id="user-password" name="password" type="password" autocomplete="new-password" {{ $user->exists ? '' : 'required' }}>
                </div>
                <div>
                    <label class="clinic-label" for="user-password-confirmation">Confirmer le mot de passe</label>
                    <input class="clinic-field" id="user-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" {{ $user->exists ? '' : 'required' }}>
                </div>
            </div>

            <div class="clinic-form-actions">
                <button class="clinic-button" type="submit">{{ $user->exists ? 'Enregistrer les modifications' : 'Créer le compte' }}</button>
                <a class="clinic-button clinic-button--secondary" href="{{ route('admin.users.index') }}">Annuler</a>
            </div>
        </form>
    </section>
</x-app-layout>
