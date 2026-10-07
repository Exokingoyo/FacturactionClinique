<x-guest-layout>
    <h1 class="clinic-auth-title">Bon retour</h1>
    <p class="clinic-auth-copy">Connectez-vous pour accéder à votre espace de gestion.</p>
    <x-auth-session-status class="clinic-alert" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="clinic-auth-form">
        @csrf

        <div>
            <label class="clinic-label" for="email">Adresse e-mail</label>
            <x-text-input id="email" class="clinic-field" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label class="clinic-label" for="password">Mot de passe</label>
            <x-text-input id="password" class="clinic-field" type="password" name="password" required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="clinic-checkbox rounded border-gray-300" name="remember">
                <span class="ms-2 text-sm text-gray-600">Se souvenir de moi</span>
            </label>
        </div>

        <div class="clinic-auth-footer">
            @if (Route::has('password.request'))
                <a class="clinic-link text-sm" href="{{ route('password.request') }}">
                    Mot de passe oublié ?
                </a>
            @endif

            <button class="clinic-button" type="submit">Se connecter</button>
        </div>
    </form>
</x-guest-layout>
