<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reçu {{ $facture->numero }}</title>
    <style>
        @page { size: 80mm {{ number_format($paperHeightMm, 2, '.', '') }}mm; margin: 4mm; }
        * { box-sizing: border-box; }
        body {
            width: 72mm;
            margin: 0 auto;
            color: #252525;
            font: 9px/1.35 Arial, sans-serif;
        }
        .receipt { width: 100%; }
        h1 { margin: 0 0 8px; font-size: 18px; font-weight: normal; text-align: center; }
        .clinic, .patient, .thanks { text-align: center; }
        .clinic { margin-bottom: 8px; }
        .separator { height: 0; margin: 7px 0; border-top: 1px dashed #555; }
        .meta { margin: 2px 0; overflow-wrap: anywhere; }
        table { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .line-heading td:first-child { width: 68%; overflow-wrap: anywhere; }
        .line-heading td:last-child { width: 32%; text-align: right; white-space: nowrap; }
        .total { margin-top: 8px; font-weight: bold; }
        .total td:last-child { text-align: right; white-space: nowrap; }
        .thanks { margin: 14px 0 0; font-size: 15px; }
        .issued-by { margin-top: 10px; text-align: center; font-size: 8px; overflow-wrap: anywhere; }
        .print-actions { display: flex; justify-content: center; gap: 10px; margin: 20px auto; }
        .print-actions a, .print-actions button {
            padding: 10px 14px;
            border: 1px solid #166a6d;
            border-radius: 6px;
            background: #166a6d;
            color: #fff;
            font: inherit;
            text-decoration: none;
            cursor: pointer;
        }
        .print-actions a { background: #fff; color: #166a6d; }
        @media screen {
            body { margin-top: 24px; }
            .receipt { padding: 12px; box-shadow: 0 2px 12px #0002; }
        }
        @media print {
            .print-actions { display: none; }
            body { width: auto; margin: 0; }
        }
    </style>
</head>
<body>
    <main class="receipt">
        <h1>REÇU</h1>
        <div class="clinic">
            <strong>{{ config('clinique.nom') }}</strong><br>
            {{ config('clinique.adresse') }}
        </div>

        <div class="separator"></div>
        <div class="meta">N° {{ $facture->numero }}</div>
        <div class="meta">Le {{ $facture->created_at->format('d/m/Y H:i') }}</div>
        <div class="meta">Nom : {{ $facture->patient_nom }}</div>
        @if ($facture->patient_telephone)
            <div class="meta">Téléphone : {{ $facture->patient_telephone }}</div>
        @endif

        <div class="separator"></div>
        @foreach ($facture->lignes as $ligne)
            <table class="line-heading">
                <tr>
                    <td>{{ $ligne->designation }}</td>
                    <td>{{ number_format($ligne->prix * $ligne->quantite, 2) }} {{ config('clinique.devise') }}</td>
                </tr>
            </table>
        @endforeach
        <div class="separator"></div>
        <table class="total">
            <tr>
                <td>TOTAL :</td>
                <td>{{ number_format($facture->total, 2) }} {{ config('clinique.devise') }}</td>
            </tr>
        </table>
        <div class="separator"></div>
        <p class="thanks">MERCI !</p>
        <div class="issued-by">Facture établie par {{ $facture->user->name }}</div>
    </main>

    <nav class="print-actions" aria-label="Actions d’impression">
        <button type="button" data-print>Imprimer</button>
        <a href="{{ route('factures.pdf', $facture) }}" download="{{ $facture->numero }}.pdf">Télécharger le PDF</a>
    </nav>
    <script>
        document.querySelector('[data-print]').addEventListener('click', () => window.print());
    </script>
</body>
</html>
