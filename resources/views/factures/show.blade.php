<x-app-layout>
    <section class="clinic-page clinic-page--narrow">
        <div class="clinic-page__heading">
            <div>
                <p class="clinic-eyebrow">Détail de la facture</p>
                <h1 class="clinic-title">{{ $facture->numero }}</h1>
                <p class="clinic-subtitle">Créée le {{ $facture->created_at->format('d/m/Y à H:i') }} par {{ $facture->user->name }}</p>
            </div>
            <div class="clinic-heading-actions">
                @if (Auth::user()->isAdmin())
                    <a class="clinic-button" href="{{ route('admin.factures.edit', $facture) }}">Modifier la facture</a>
                @endif
                <a class="clinic-button clinic-button--secondary" href="{{ route('factures.index') }}">← {{ Auth::user()->isAdmin() ? 'Toutes les factures' : 'Mes factures' }}</a>
            </div>
        </div>

        <article class="clinic-card clinic-card__body">
            @if (session('ok'))
                <div class="clinic-alert" role="status">{{ session('ok') }}</div>
            @endif
            <div class="flex flex-wrap items-start justify-between gap-5" style="margin-bottom:26px">
                <div>
                    <p class="clinic-eyebrow">Patient</p>
                    <h2 class="text-xl font-bold" style="margin:0">{{ $facture->patient_nom }}</h2>
                    @if ($facture->patient_telephone)
                        <p class="clinic-subtitle" style="margin-top:5px">{{ $facture->patient_telephone }}</p>
                    @endif
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-700">Émise</span>
            </div>

            <div class="clinic-card clinic-table-wrap" style="box-shadow:none">
                <table class="clinic-table">
                    <thead>
                        <tr>
                            <th scope="col">Prestation</th>
                            <th scope="col">Quantité</th>
                            <th scope="col">Prix unitaire</th>
                            <th scope="col">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($facture->lignes as $l)
                            <tr>
                                <td class="clinic-table__number">{{ $l->designation }}</td>
                                <td>{{ $l->quantite }}</td>
                                <td>{{ number_format($l->prix, 2) }} {{ config('clinique.devise') }}</td>
                                <td>{{ number_format($l->prix * $l->quantite, 2) }} {{ config('clinique.devise') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-4" style="margin-top:24px">
                <p class="clinic-total" style="margin:0">Total à payer : <strong>{{ number_format($facture->total, 2) }} {{ config('clinique.devise') }}</strong></p>
                <div class="clinic-heading-actions">
                    <a href="{{ route('factures.pdf', $facture) }}" class="clinic-button clinic-button--secondary" download="{{ $facture->numero }}.pdf">
                        Télécharger le PDF
                    </a>
                    <a href="{{ route('factures.print', $facture) }}" class="clinic-button">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M7 14h10v7H7z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Imprimer
                    </a>
                </div>
            </div>
        </article>
    </section>
</x-app-layout>
