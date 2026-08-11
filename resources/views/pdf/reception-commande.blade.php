<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bon de réception {{ $reception->numero_reception }}</title>
    <style>
        @page { margin: 30px 35px; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            color: #444;
            margin: 0;
        }

        /* ---------- En-tête ---------- */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .header-table td {
            vertical-align: top;
        }
        .company-cell {
            width: 55%;
            font-size: 11px;
            color: #555;
            line-height: 1.6;
        }
        .company-cell .company-name {
            font-weight: bold;
            color: #333;
            font-size: 12px;
        }
        .title-cell {
            width: 45%;
            text-align: right;
        }
        .title-cell h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 300;
            color: #9aa0c0;
            letter-spacing: 4px;
        }
        .title-cell .meta {
            margin-top: 10px;
            font-size: 10.5px;
            color: #555;
        }
        .title-cell .meta .val {
            display: inline-block;
            min-width: 110px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 1px;
            text-align: left;
            margin-left: 4px;
        }

        /* ---------- Fournisseur / Réception ---------- */
        .parties-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .parties-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }
        .parties-table .section-label {
            color: #8b93c7;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .field-line {
            margin-bottom: 7px;
            font-size: 10.5px;
        }
        .field-line .fl-label {
            color: #999;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.3px;
            display: block;
        }
        .field-line .fl-value {
            display: inline-block;
            border-bottom: 1px solid #ccc;
            width: 100%;
            padding-bottom: 2px;
            min-height: 12px;
        }

        /* ---------- Bandeau infos livraison ---------- */
        table.ship-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.ship-info th {
            background: #8b93c7;
            color: #fff;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-weight: normal;
            padding: 6px 8px;
            text-align: left;
        }
        table.ship-info td {
            background: #eceefa;
            padding: 8px;
            font-size: 10.5px;
            border-bottom: 1px solid #fff;
        }

        /* ---------- Tableau détail ---------- */
        table.details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        table.details th {
            background: #8b93c7;
            color: #fff;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-weight: normal;
            padding: 7px 8px;
            text-align: left;
        }
        table.details td {
            padding: 6px 8px;
            font-size: 10.5px;
            border-bottom: 1px solid #e3e5f0;
        }
        table.details tbody tr:nth-child(even) {
            background: #f6f7fb;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* ---------- Bas de page : remarques + totaux ---------- */
        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .bottom-table td {
            vertical-align: top;
        }
        .remarks-cell {
            width: 55%;
            padding-right: 25px;
        }
        .remarks-cell .section-label {
            color: #8b93c7;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .remarks-box {
            background: #f6f7fb;
            border: 1px solid #e3e5f0;
            min-height: 70px;
            padding: 8px;
            font-size: 10.5px;
            color: #555;
        }

        .totals-cell {
            width: 45%;
        }
        table.totals {
            width: 100%;
            border-collapse: collapse;
        }
        table.totals td {
            padding: 5px 0;
            font-size: 10.5px;
        }
        table.totals .t-label {
            color: #666;
        }
        table.totals .t-value {
            text-align: right;
            border-bottom: 1px solid #ccc;
            min-width: 90px;
        }
        table.totals .grand-total td {
            font-weight: bold;
            font-size: 12px;
            color: #333;
            padding-top: 8px;
            border-top: 1px solid #8b93c7;
        }
        table.totals .grand-total .t-value {
            border-bottom: none;
        }

        /* ---------- Signature ---------- */
        .signature-row {
            margin-top: 20px;
        }
        .signature-row .fl-label {
            color: #999;
            text-transform: uppercase;
            font-size: 9px;
        }
        .signature-row .t-value {
            display: inline-block;
            border-bottom: 1px solid #ccc;
            min-width: 150px;
            min-height: 30px;
        }

        .footer {
            margin-top: 30px;
            font-size: 8.5px;
            color: #aaa;
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9.5px;
            font-weight: bold;
            color: #fff;
        }
    </style>
</head>
<body>

{{-- En-tête : Magasin (société) + titre --}}
<table class="header-table">
    <tr>
        <td class="company-cell">
            <div class="company-name">{{ $magasin?->name ?? 'Magasin' }}</div>
            <div>{{ $magasin?->adresse ?? '-' }}</div>
            @if($magasin?->telephone)
                <div>Tél : {{ $magasin->telephone }}</div>
            @endif
        </td>
        <td class="title-cell">
            <h1>BON DE RÉCEPTION</h1>
            <div class="meta">
                DATE <span class="val">{{ $formattedDate }}</span>
            </div>
            <div class="meta">
                N° RÉCEPTION <span class="val">{{ $reception->numero_reception }}</span>
            </div>
        </td>
    </tr>
</table>

{{-- Fournisseur / Réception --}}
<table class="parties-table">
    <tr>
        <td>
            <div class="section-label">Fournisseur</div>

            <div class="field-line">
                <span class="fl-label">Société</span>
                <span class="fl-value">{{ $fournisseur?->raison_social ?? $fournisseur?->name ?? '-' }}</span>
            </div>
            <div class="field-line">
                <span class="fl-label">Adresse</span>
                <span class="fl-value">{{ $fournisseur?->adresse_siege ?? '-' }}</span>
            </div>
            <div class="field-line">
                <span class="fl-label">Ville</span>
                <span class="fl-value">{{ trim(($fournisseur?->code_postal ?? '') . ' ' . ($fournisseur?->ville ?? '')) ?: '-' }}</span>
            </div>
            <div class="field-line">
                <span class="fl-label">Téléphone</span>
                <span class="fl-value">{{ $fournisseur?->telephone ?? '-' }}</span>
            </div>
        </td>
        <td>
            <div class="section-label">Réception</div>

            <div class="field-line">
                <span class="fl-label">N° commande</span>
                <span class="fl-value">{{ $reception->commande?->numero_commande ?? '-' }}</span>
            </div>
            <div class="field-line">
                <span class="fl-label">Bon de commande</span>
                <span class="fl-value">{{ $reception->bonCommande?->numero ?? '-' }}</span>
            </div>
            <div class="field-line">
                <span class="fl-label">Réceptionné par</span>
                <span class="fl-value">{{ $reception->receivedBy?->name ?? '-' }}</span>
            </div>
            <div class="field-line">
                <span class="fl-label">Statut</span>
                <span class="fl-value">
                        <span class="badge" style="background: {{ match($reception->statut) {
                            'en_cours' => '#d97706',
                            'partielle' => '#2563eb',
                            'complete' => '#059669',
                            'annulee' => '#dc2626',
                            default => '#8b93c7',
                        } }};">
                            {{ match($reception->statut) {
                                'en_cours' => 'EN COURS',
                                'partielle' => 'PARTIELLE',
                                'complete' => 'COMPLÈTE',
                                'annulee' => 'ANNULÉE',
                                default => strtoupper($reception->statut),
                            } }}
                        </span>
                    </span>
            </div>
        </td>
    </tr>
</table>

{{-- Bandeau infos livraison --}}
<table class="ship-info">
    <thead>
    <tr>
        <th style="width: 30%;">Fournisseur</th>
        <th style="width: 25%;">Date de réception</th>
        <th style="width: 25%;">N° bon livraison</th>
        <th style="width: 20%;">Nb lignes</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>{{ $fournisseur?->name ?? '-' }}</td>
        <td>{{ $formattedDate }}</td>
        <td>{{ $reception->numero_bl ?? '-' }}</td>
        <td>{{ $reception->details->count() }}</td>
    </tr>
    </tbody>
</table>

{{-- Tableau des lignes --}}
<table class="details">
    <thead>
    <tr>
        <th style="width: 10%;">Code</th>
        <th style="width: 28%;">Nom du produit / description</th>
        <th style="width: 10%;" class="text-center">Qté cmd.</th>
        <th style="width: 10%;" class="text-center">Qté reçue</th>
        <th style="width: 11%;" class="text-center">Qté invend.</th>
        <th style="width: 11%;" class="text-center">Qté vendable</th>
        <th style="width: 10%;">Motif</th>
        <th style="width: 10%;">Commentaire</th>
    </tr>
    </thead>
    <tbody>
    @foreach($reception->details as $detail)
        <tr>
            <td>{{ $detail->product?->EAN ?? '-' }}</td>
            <td>{{ $detail->product?->designation ?? '-' }}</td>
            <td class="text-center">{{ $detail->detailCommande?->quantite ?? '-' }}</td>
            <td class="text-center">{{ $detail->qte_recue }}</td>
            <td class="text-center">{{ $detail->qte_invendable }}</td>
            <td class="text-center">{{ $detail->qte_bonne }}</td>
            <td>{{ $detail->motif_invendable ?? '-' }}</td>
            <td>{{ $detail->commentaire ?? '-' }}</td>
        </tr>
    @endforeach

    @if($reception->details->isEmpty())
        <tr>
            <td colspan="8" class="text-center">Aucune ligne de réception</td>
        </tr>
    @endif
    </tbody>
</table>

{{-- Remarques + totaux --}}
<table class="bottom-table">
    <tr>
        <td class="remarks-cell">
            <div class="section-label">Remarques / notes</div>
            <div class="remarks-box">{{ $reception->commentaire ?? '—' }}</div>

            <div class="signature-row">
                <span class="fl-label">Signature</span><br>
                <span class="t-value"></span>
            </div>
        </td>
        <td class="totals-cell">
            <table class="totals">
                <tr>
                    <td class="t-label">Total quantité commandée</td>
                    <td class="t-value">{{ $reception->details->sum(fn ($d) => $d->detailCommande?->quantite ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="t-label">Total quantité reçue</td>
                    <td class="t-value">{{ $reception->qte_totale_recue }}</td>
                </tr>
                <tr>
                    <td class="t-label">Total quantité invendable</td>
                    <td class="t-value">{{ $reception->qte_totale_invendable }}</td>
                </tr>
                <tr class="grand-total">
                    <td>Total quantité vendable</td>
                    <td class="t-value">{{ $reception->qte_totale_recue - $reception->qte_totale_invendable }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="footer">
    Document généré le {{ now()->format('d/m/Y à H:i') }} — {{ $reception->numero_reception }}
</div>

</body>
</html>
