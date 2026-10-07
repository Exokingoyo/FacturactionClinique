<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; }
        h1 { margin: 0; font-size: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #999; padding: 6px; text-align: left; }
        .r { text-align: right; }
        .total { margin-top: 10px; text-align: right; font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ config('clinique.nom') }}</h1>
    <div>{{ config('clinique.adresse') }}</div>
    <hr>
    <h2>Facture {{ $facture->numero }}</h2>
    <div>Date : {{ $facture->created_at->format('d/m/Y H:i') }}</div>
    <div>Patient : <b>{{ $facture->patient_nom }}</b></div>
    @if ($facture->patient_telephone)<div>Téléphone : {{ $facture->patient_telephone }}</div>@endif

    <table>
        <tr><th>Examen / service</th><th class="r">Qté</th><th class="r">Prix unit.</th><th class="r">Montant</th></tr>
        @foreach ($facture->lignes as $l)
            <tr>
                <td>{{ $l->designation }}</td>
                <td class="r">{{ $l->quantite }}</td>
                <td class="r">{{ number_format($l->prix, 2) }}</td>
                <td class="r">{{ number_format($l->prix * $l->quantite, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <div class="total">TOTAL : {{ number_format($facture->total, 2) }} {{ config('clinique.devise') }}</div>
    <p style="margin-top:40px">Facture établie par : {{ $facture->user->name }}</p>
</body>
</html>
