<nav x-data="{ open: false }" class="clinic-navbar">
    <div class="clinic-navbar__inner">
        <a class="clinic-brand" href="{{ route(Auth::user()->isAdmin() ? 'admin.dashboard' : 'dashboard') }}" aria-label="Clinique — accueil">
            <span class="clinic-brand__mark" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M10 3h4v7h7v4h-7v7h-4v-7H3v-4h7V3Z" fill="currentColor"/>
                </svg>
            </span>
            <span>{{ config('clinique.nom', 'Clinique') }}</span>
        </a>

        <div class="clinic-nav" aria-label="Navigation principale">
            <a class="clinic-nav__link {{ request()->routeIs('dashboard', 'admin.dashboard') ? 'is-active' : '' }}" href="{{ route(Auth::user()->isAdmin() ? 'admin.dashboard' : 'dashboard') }}">Accueil</a>
            <a class="clinic-nav__link {{ request()->routeIs('factures.create') ? 'is-active' : '' }}" href="{{ route('factures.create') }}">Nouvelle facture</a>
            <a class="clinic-nav__link {{ request()->routeIs('factures.index', 'factures.show') ? 'is-active' : '' }}" href="{{ route('factures.index') }}">Factures</a>
            @if (Auth::user()->isAdmin())
                <a class="clinic-nav__link {{ request()->routeIs('admin.services.*') ? 'is-active' : '' }}" href="{{ route('admin.services.index') }}">Services</a>
                <a class="clinic-nav__link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}">Utilisateurs</a>
            @endif
        </div>

        <div class="clinic-user" x-data="{ accountOpen: false }" @click.outside="accountOpen = false">
            <button class="clinic-user__trigger" type="button" @click="accountOpen = !accountOpen" :aria-expanded="accountOpen.toString()" aria-label="Menu du compte">
                <span class="clinic-user__avatar" aria-hidden="true">{{ mb_substr(Auth::user()->name, 0, 1) }}</span>
                <span class="clinic-user__name">{{ Auth::user()->name }}</span>
                <svg width="16" height="16" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="clinic-user__menu" x-cloak x-show="accountOpen" x-transition>
                <a href="{{ route('profile.edit') }}">Mon profil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit">Se déconnecter</button>
                </form>
            </div>
        </div>

        <button class="clinic-mobile-toggle" type="button" @click="open = !open" :aria-expanded="open.toString()" aria-controls="clinic-mobile-menu" aria-label="Ouvrir le menu">
            <svg x-show="!open" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <svg x-cloak x-show="open" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
    </div>

    <div id="clinic-mobile-menu" class="clinic-mobile-menu" x-cloak x-show="open" x-transition>
        <div class="clinic-nav" aria-label="Navigation mobile">
            <a class="clinic-nav__link {{ request()->routeIs('dashboard', 'admin.dashboard') ? 'is-active' : '' }}" href="{{ route(Auth::user()->isAdmin() ? 'admin.dashboard' : 'dashboard') }}">Accueil</a>
            <a class="clinic-nav__link {{ request()->routeIs('factures.create') ? 'is-active' : '' }}" href="{{ route('factures.create') }}">Nouvelle facture</a>
            <a class="clinic-nav__link {{ request()->routeIs('factures.index', 'factures.show') ? 'is-active' : '' }}" href="{{ route('factures.index') }}">Factures</a>
            @if (Auth::user()->isAdmin())
                <a class="clinic-nav__link {{ request()->routeIs('admin.services.*') ? 'is-active' : '' }}" href="{{ route('admin.services.index') }}">Services</a>
                <a class="clinic-nav__link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}">Utilisateurs</a>
            @endif
        </div>
        <div class="clinic-mobile-menu__account">
            <div class="clinic-mobile-menu__meta">
                <strong>{{ Auth::user()->name }}</strong>
                {{ Auth::user()->email }}
            </div>
            <a class="clinic-nav__link" href="{{ route('profile.edit') }}">Mon profil</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="clinic-nav__link" type="submit">Se déconnecter</button>
            </form>
        </div>
    </div>
</nav>
