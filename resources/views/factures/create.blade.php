<x-app-layout>
    
    <section class="clinic-page clinic-page--narrow">
        <div class="clinic-page__heading">
            <div>
                <p class="clinic-eyebrow">Facturation</p>
                <h1 class="clinic-title">Nouvelle facture</h1>
                <p class="clinic-subtitle">Renseignez les informations du patient et sélectionnez les prestations.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="clinic-alert clinic-alert--error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('factures.store') }}" class="clinic-card clinic-card__body" data-offline-invoice data-sync-url="{{ route('factures.synchronize') }}" data-user-id="{{ auth()->id() }}" data-catalog-proof="{{ $catalogProof }}">
            @csrf
            <div class="clinic-form-grid" style="margin-bottom:28px">
                <div>
                    <label class="clinic-label" for="patient-name">Nom du patient</label>
                    <input id="patient-name" name="patient_nom" value="{{ old('patient_nom') }}" placeholder="Ex. Amadou Diallo" class="clinic-field" required autofocus autocomplete="name">
                </div>
                <div>
                    <label class="clinic-label" for="patient-phone">Téléphone <span class="font-normal text-gray-500">(facultatif)</span></label>
                    <input id="patient-phone" name="patient_telephone" value="{{ old('patient_telephone') }}" placeholder="Ex. +221 77 000 00 00" class="clinic-field" autocomplete="tel">
                </div>
            </div>

            <div class="flex items-end justify-between gap-3" style="margin-bottom:12px">
                <div>
                    <h2 class="text-lg font-bold" style="margin:0">Prestations</h2>
                    <p class="text-sm text-gray-500" style="margin:4px 0 0">Cochez les actes à inclure dans la facture.</p>
                </div>
                <span class="text-sm text-gray-500">{{ $services->count() }} disponible(s)</span>
            </div>

            <div class="clinic-table-wrap" style="margin-bottom:20px">
                @forelse ($services as $s)
                    <label class="clinic-check-row clinic-service-row" for="s{{ $s->id }}">
                        <input type="checkbox" name="services[]" value="{{ $s->id }}" data-prix="{{ $s->prix }}" class="chk clinic-checkbox" id="s{{ $s->id }}" @checked(in_array($s->id, old('services', [])))>
                        <span class="font-semibold text-gray-800">{{ $s->nom }}</span>
                        <span class="clinic-check-row__price">{{ number_format($s->prix, 2) }} {{ config('clinique.devise') }}</span>
                        <input type="number" name="quantites[{{ $s->id }}]" value="{{ old('quantites.' . $s->id, 1) }}" min="1" aria-label="Quantité pour {{ $s->nom }}" class="clinic-field qte">
                    </label>
                @empty
                    <div class="clinic-empty">
                        Aucun service n’est encore configuré. Demandez à un administrateur d’ajouter les prestations.
                    </div>
                @endforelse
            </div>

            <div class="clinic-mobile-actions flex items-center justify-between gap-4">
                <p class="clinic-total" style="margin:0">Total : <strong><span id="total" aria-live="polite">0.00</span> {{ config('clinique.devise') }}</strong></p>
                <button class="clinic-button" type="submit" @if ($services->isEmpty()) disabled @endif>
                    Générer la facture
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
            <div class="clinic-alert clinic-alert--error" data-offline-form-status role="status" aria-live="polite" hidden></div>
        </form>
    </section>

    <script>
        (() => {
            const total = document.getElementById('total');
            const calculate = () => {
                let sum = 0;
                document.querySelectorAll('.chk:checked').forEach((checkbox) => {
                    const quantity = Number.parseInt(checkbox.closest('.clinic-check-row').querySelector('.qte').value, 10) || 1;
                    sum += Number.parseFloat(checkbox.dataset.prix) * Math.max(1, quantity);
                });
                total.textContent = sum.toFixed(2);
            };

            document.querySelectorAll('.chk, .qte').forEach((field) => {
                field.addEventListener('input', calculate);
                field.addEventListener('change', calculate);
            });
            calculate();
        })();
    </script>
</x-app-layout>
