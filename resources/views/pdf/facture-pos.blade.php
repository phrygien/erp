<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture #{{ str_pad($vente->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page { margin: 30px 40px; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1f2937;
        }
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header table { width: 100%; }
        .header h1 { font-size: 20px; margin: 0 0 4px 0; }
        .header .societe { font-size: 11px; color: #6b7280; }
        .facture-meta { text-align: right; }
        .facture-meta .numero { font-size: 16px; font-weight: bold; }
        .facture-meta .date { color: #6b7280; }

        table.lignes {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.lignes th {
            background: #f3f4f6;
            text-align: left;
            padding: 8px;
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
        }
        table.lignes td {
            padding: 8px;
            border-bottom: 1px solid #f3f4f6;
        }
        table.lignes .num { text-align: right; }

        .totaux {
            width: 40%;
            margin-left: auto;
            margin-top: 16px;
        }
        .totaux table { width: 100%; }
        .totaux td { padding: 4px 8px; }
        .totaux .label { color: #6b7280; }
        .totaux .valeur { text-align: right; }
        .totaux .total-final {
            font-size: 15px;
            font-weight: bold;
            border-top: 2px solid #111827;
            padding-top: 8px;
        }

        .paiement {
            margin-top: 16px;
            padding: 10px 12px;
            background: #f9fafb;
            border-radius: 6px;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
        }
    </style>
</head>
<body>

<div class="header">
    <table>
        <tr>
            <td>
                <h1>{{ config('app.name', 'Ma Société') }}</h1>
                <div class="societe">
                    {{-- Adaptez avec vos vraies coordonnées --}}
                    123 Rue du Commerce, 75001 Paris<br>
                    SIRET : 000 000 000 00000<br>
                    contact@masociete.fr
                </div>
            </td>
            <td class="facture-meta">
                <div class="numero">FACTURE N° {{ str_pad($vente->id, 6, '0', STR_PAD_LEFT) }}</div>
                <div class="date">{{ $vente->created_at->format('d/m/Y à H:i') }}</div>
                @if ($vente->user)
                    <div class="date">Vendeur : {{ $vente->user->name }}</div>
                @endif
            </td>
        </tr>
    </table>
</div>

<table class="lignes">
    <thead>
    <tr>
        <th>Désignation</th>
        <th class="num">Qté</th>
        <th class="num">Prix unitaire</th>
        <th class="num">Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($vente->lignes as $ligne)
        <tr>
            <td>{{ $ligne->designation }}</td>
            <td class="num">{{ $ligne->quantite }}</td>
            <td class="num">{{ number_format($ligne->prix_unitaire, 2) }} EUR</td>
            <td class="num">{{ number_format($ligne->total_ligne, 2) }} EUR</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="totaux">
    <table>
        <tr>
            <td class="label">Sous-total</td>
            <td class="valeur">{{ number_format($vente->total, 2) }} EUR</td>
        </tr>
        <tr>
            <td class="label total-final">Total TTC</td>
            <td class="valeur total-final">{{ number_format($vente->total, 2) }} EUR</td>
        </tr>
    </table>
</div>

<div class="paiement">
    <strong>Paiement en espèces</strong><br>
    Reçu : {{ number_format($vente->especes_recues, 2) }} EUR<br>
    Rendu : {{ number_format($vente->monnaie_rendue, 2) }} EUR
</div>

<div class="footer">
    Merci de votre achat — Facture générée automatiquement le {{ now()->format('d/m/Y à H:i') }}
</div>

</body>
</html>
