<x-app-layout>
    <section class="clinic-page clinic-page--narrow">
        <div class="clinic-page__heading">
            <div>
                <p class="clinic-eyebrow">Administration · Facturation</p>
                <h1 class="clinic-title">Modifier {{ $facture->numero }}</h1>
                <p class="clinic-subtitle">Modifiez les informations du patient et les lignes de facture.</p>
            </div>
            <a class="clinic-button clinic-button--secondary" href="{{ route('factures.show', $facture) }}">← Retour à la facture</a>
        </div>

        @if ($errors->any())
            <div class="clinic-alert clinic-alert--error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form class="clinic-card clinic-card__body" method="POST" action="{{ route('admin.factures.update', $facture) }}" id="edit-invoice-form">
            @csrf
            @method('PUT')

            <div class="clinic-form-grid" style="margin-bottom:28px">
                <div>
                    <label class="clinic-label" for="patient-name">Nom du patient</label>
                    <input class="clinic-field" id="patient-name" name="patient_nom" value="{{ old('patient_nom', $facture->patient_nom) }}" required>
                </div>
                <div>
                    <label class="clinic-label" for="patient-phone">Téléphone</label>
                    <input class="clinic-field" id="patient-phone" name="patient_telephone" value="{{ old('patient_telephone', $facture->patient_telephone) }}">
                </div>
            </div>

            <div class="clinic-admin-section-heading clinic-invoice-lines-heading">
                <div>
                    <h2 class="clinic-section-title">Lignes de facture</h2>
                    <p class="clinic-subtitle">Modifiez les prestations, prix et quantités.</p>
                </div>
                <button class="clinic-button clinic-button--secondary" type="button" id="add-invoice-line">+ Ajouter une ligne</button>
            </div>

            <div id="invoice-lines" class="clinic-invoice-lines">
                @foreach ($facture->lignes as $index => $line)
                    <div class="clinic-invoice-line">
                        <input type="hidden" name="lignes[{{ $index }}][id]" value="{{ $line->id }}">
                        <div>
                            <label class="clinic-label" for="line-name-{{ $index }}">Désignation</label>
                            <input class="clinic-field" id="line-name-{{ $index }}" name="lignes[{{ $index }}][designation]" value="{{ old("lignes.{$index}.designation", $line->designation) }}" required>
                        </div>
                        <div>
                            <label class="clinic-label" for="line-price-{{ $index }}">Prix unitaire</label>
                            <input class="clinic-field" id="line-price-{{ $index }}" name="lignes[{{ $index }}][prix]" type="number" min="0" step="0.01" value="{{ old("lignes.{$index}.prix", $line->prix) }}" required>
                        </div>
                        <div>
                            <label class="clinic-label" for="line-quantity-{{ $index }}">Quantité</label>
                            <input class="clinic-field" id="line-quantity-{{ $index }}" name="lignes[{{ $index }}][quantite]" type="number" min="1" step="1" value="{{ old("lignes.{$index}.quantite", $line->quantite) }}" required>
                        </div>
                        <button class="clinic-line-remove" type="button" aria-label="Supprimer cette ligne">Retirer</button>
                    </div>
                @endforeach
            </div>

            <template id="invoice-line-template">
                <div class="clinic-invoice-line">
                    <div>
                        <label class="clinic-label">Désignation</label>
                        <input class="clinic-field" data-name="designation" required>
                    </div>
                    <div>
                        <label class="clinic-label">Prix unitaire</label>
                        <input class="clinic-field" data-name="prix" type="number" min="0" step="0.01" value="0" required>
                    </div>
                    <div>
                        <label class="clinic-label">Quantité</label>
                        <input class="clinic-field" data-name="quantite" type="number" min="1" step="1" value="1" required>
                    </div>
                    <button class="clinic-line-remove" type="button" aria-label="Supprimer cette ligne">Retirer</button>
                </div>
            </template>

            <div class="clinic-form-actions">
                <button class="clinic-button" type="submit">Enregistrer la facture</button>
                <a class="clinic-button clinic-button--secondary" href="{{ route('factures.show', $facture) }}">Annuler</a>
            </div>
        </form>
    </section>

    <script>
        (() => {
            const lines = document.getElementById('invoice-lines');
            const template = document.getElementById('invoice-line-template');
            let nextIndex = {{ $facture->lignes->count() }};

            document.getElementById('add-invoice-line').addEventListener('click', () => {
                const fragment = template.content.cloneNode(true);
                fragment.querySelectorAll('[data-name]').forEach((field) => {
                    field.name = `lignes[${nextIndex}][${field.dataset.name}]`;
                });
                nextIndex += 1;
                lines.appendChild(fragment);
            });

            lines.addEventListener('click', (event) => {
                if (!event.target.matches('.clinic-line-remove')) return;
                const currentLines = lines.querySelectorAll('.clinic-invoice-line');
                if (currentLines.length <= 1) {
                    currentLines[0].querySelectorAll('input:not([type="hidden"])').forEach((field) => {
                        field.value = field.type === 'number' && field.name.endsWith('[quantite]') ? '1' : '';
                    });
                    currentLines[0].querySelector('input[type="hidden"]')?.remove();
                    return;
                }
                event.target.closest('.clinic-invoice-line').remove();
            });
        })();
    </script>
</x-app-layout>
