<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 4mm; }
        body {
            color: #252525;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.35;
        }
        .receipt { width: 100%; }
        .title {
            margin: 0 0 8px;
            font-family: DejaVu Sans, sans-serif;
            font-size: 18px;
            font-weight: normal;
            text-align: center;
        }
        .clinic, .patient, .thanks { text-align: center; }
        .clinic { margin-bottom: 8px; }
        .separator {
            margin: 7px 0;
            border-top: 1px dashed #555;
            height: 0;
        }
        .meta { margin: 2px 0; }
        .line-heading, .total { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .line-heading td:first-child { width: 68%; overflow-wrap: break-word; }
        .line-heading td:last-child { width: 32%; }
        .line-amount { text-align: right; white-space: nowrap; }
        .total { margin-top: 8px; font-weight: bold; }
        .total-label { text-align: left; }
        .total-amount { text-align: right; white-space: nowrap; }
        .thanks {
            margin: 14px 0 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 15px;
        }
        .issued-by { margin-top: 10px; text-align: center; font-size: 8px; }
    </style>
</head>
<body>
    <div class="receipt">
        <h1 class="title">REÇU</h1>
        <div class="clinic">
            <strong>{{ config('clinique.nom') }}</strong><br>
            {{ config('clinique.adresse') }}
        </div>

        <div class="separator"></div>
        <div class="meta">N° {{ $facture->numero }}</div>
        <div class="meta">Le {{ $facture->created_at->format('d/m/Y H:i') }}</div>
        <div class="meta"> Nom: {{ $facture->patient_nom }} </div>
        @if ($facture->patient_telephone)
            <div class="meta">Téléphone : {{ $facture->patient_telephone }}</div>
        @endif

        <div class="separator"></div>

        @foreach ($facture->lignes as $ligne)
            <table class="line-heading">
                <tr>
                    <td>{{ $ligne->designation }}</td>
                    <td class="line-amount">{{ number_format($ligne->prix * $ligne->quantite, 2) }} {{ config('clinique.devise') }}</td>
                </tr>
            </table>
        @endforeach

        <div class="separator"></div>
        <table class="total">
            <tr>
                <td class="total-label">TOTAL :</td>
                <td class="total-amount">{{ number_format($facture->total, 2) }} {{ config('clinique.devise') }}</td>
            </tr>
        </table>
        <div class="separator"></div>

        <p class="thanks">MERCI !</p>
        <div class="issued-by">Facture établie par {{ $facture->user->name }}</div>
    </div>
</body>
</html>
