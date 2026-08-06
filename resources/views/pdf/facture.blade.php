<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $facture->numero_facture }}</title>
    <style>
        {{--
            Police Jost : place les fichiers .ttf dans public/fonts/
            (téléchargeables sur https://fonts.google.com/specimen/Jost)
              - Jost-Regular.ttf
              - Jost-Medium.ttf
              - Jost-SemiBold.ttf
              - Jost-Italic.ttf
            Barryvdh/laravel-dompdf autorise les chemins absolus locaux
            via public_path() sans avoir à activer le fetch distant.
        --}}
        @font-face {
            font-family: 'Jost';
            src: url('{{ public_path('fonts/Jost-Regular.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Jost';
            src: url('{{ public_path('fonts/Jost-Medium.ttf') }}') format('truetype');
            font-weight: 500;
            font-style: normal;
        }

        @font-face {
            font-family: 'Jost';
            src: url('{{ public_path('fonts/Jost-SemiBold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        @font-face {
            font-family: 'Jost';
            src: url('{{ public_path('fonts/Jost-Italic.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: italic;
        }

        @page {
            margin: 32px 40px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Jost', 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
        }

        .box-bg {
            background-color: #cdeff4;
        }

        /* ---------- En-tête ---------- */
        .top-table {
            width: 100%;
            margin-bottom: 18px;
        }

        .top-table td {
            vertical-align: top;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .company-address {
            font-size: 10.5px;
            line-height: 1.6;
            color: #1a1a1a;
        }

        .facture-title {
            text-align: right;
            font-size: 22px;
            font-weight: 500;
            letter-spacing: 8px;
            padding-top: 4px;
        }

        /* ---------- Bloc référence / client ---------- */
        .mid-table {
            width: 100%;
            margin-bottom: 22px;
        }

        .mid-table td {
            vertical-align: top;
        }

        .ref-box {
            width: 46%;
            padding: 10px 14px;
        }

        .ref-box table {
            width: 100%;
            border-collapse: collapse;
        }

        .ref-box td {
            padding: 3px 0;
            font-size: 11px;
        }

        .ref-label {
            width: 90px;
            font-weight: 500;
        }

        .ref-dots {
            border-bottom: 1px dotted #1a1a1a;
        }

        .client-block {
            width: 54%;
            padding-left: 24px;
        }

        .client-title {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .client-address {
            font-size: 10.5px;
            line-height: 1.6;
        }

        /* ---------- Intitulé ---------- */
        .intitule {
            font-size: 11px;
            margin-bottom: 10px;
        }

        /* ---------- Tableau lignes ---------- */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 22px;
        }

        .items-table th {
            background-color: #cdeff4;
            border: 1px solid #1a1a1a;
            padding: 7px 8px;
            font-size: 10.5px;
            font-weight: bold;
            text-align: left;
        }

        .items-table td {
            border: 1px solid #1a1a1a;
            padding: 7px 8px;
            font-size: 10.5px;
            font-style: italic;
        }

        .items-table td.product-cell {
            font-style: normal;
        }

        .items-table .product-designation {
            font-style: italic;
        }

        .items-table .product-meta {
            font-size: 9px;
            font-style: normal;
            color: #555555;
            margin-top: 2px;
        }

        .items-table .empty-row td {
            height: 22px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* ---------- Totaux ---------- */
        .totals-wrapper {
            width: 100%;
            margin-bottom: 26px;
        }

        .totals-table {
            width: 260px;
            margin-left: auto;
            border-collapse: collapse;
            border: 1px solid #1a1a1a;
        }

        .totals-table td {
            padding: 7px 12px;
            font-size: 11px;
            border-bottom: 1px solid #1a1a1a;
        }

        .totals-table tr:last-child td {
            border-bottom: none;
        }

        .totals-table .totals-value {
            text-align: right;
        }

        .totals-table .total-final td {
            font-weight: bold;
            font-size: 13px;
        }

        /* ---------- Formule de politesse ---------- */
        .closing {
            font-size: 11px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        /* ---------- Pied de page ---------- */
        .footer {
            font-size: 8.5px;
            color: #9a9a9a;
            line-height: 1.7;
        }

        .footer .legal {
            text-align: center;
            margin-top: 8px;
        }
    </style>
</head>
<body>

{{-- ============ En-tête ============ --}}
{{--
    Émetteur = fournisseur (c'est lui qui facture).
    Destinataire = magasin (celui qui règle la facture).
    $magasin est passé depuis l'action ; adapte la relation
    Facture -> Magasin selon ton schéma (ex: via bonCommande).
--}}
<table class="top-table">
    <tr>
        <td style="width: 60%;">
            <div class="company-name">{{ $facture->fournisseur->raison_social ?? $facture->fournisseur->name ?? 'Le nom de votre société' }}</div>
            <div class="company-address">
                {{ $facture->fournisseur->adresse_siege ?? 'Adresse' }}<br>
                {{ trim(($facture->fournisseur->code_postal ?? '').' '.($facture->fournisseur->ville ?? '')) ?: 'CP Ville' }}<br>
                {{ $facture->fournisseur->telephone ?? 'Téléphone / Fax' }}<br>
                {{ $facture->fournisseur->email ?? 'Références Internet' }}
            </div>
        </td>
        <td style="width: 40%;">
            <div class="facture-title">F A C T U R E</div>
        </td>
    </tr>
</table>

{{-- ============ Référence / Client ============ --}}
<table class="mid-table">
    <tr>
        <td class="ref-box box-bg">
            <table>
                <tr>
                    <td class="ref-label">Référence :</td>
                    <td class="ref-dots">{{ $facture->numero_facture }}</td>
                </tr>
                <tr>
                    <td class="ref-label">Date :</td>
                    <td class="ref-dots">{{ optional($facture->date_facture)->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="ref-label">N° client :</td>
                    <td class="ref-dots">{{ $facture->bonCommande->numero ?? '' }}</td>
                </tr>
            </table>
        </td>
        <td style="width: 6%;"></td>
        <td class="client-block">
            <div class="client-title">{{ $magasin->name ?? 'Société et/ou Nom du client' }}</div>
            <div class="client-address">
                {{ $magasin->adresse ?? 'Adresse' }}<br>
                {{ $magasin->telephone ?? '' }}
            </div>
        </td>
    </tr>
</table>

{{-- ============ Intitulé ============ --}}
<div class="intitule">
    Intitulé: {{ $facture->libelle_facture ?? "Description du projet et/ou Produit facturé" }}
</div>

{{-- ============ Tableau des lignes ============ --}}
<table class="items-table">
    <thead>
    <tr>
        <th style="width: 12%;">Quantité</th>
        <th style="width: 46%;">Désignation</th>
        <th style="width: 21%;" class="text-right">Prix unitaire HT</th>
        <th style="width: 21%;" class="text-right">Prix total HT</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($facture->detailFactures as $ligne)
        @php
            $product = $ligne->detailCommande->product ?? null;
        @endphp
        <tr>
            <td class="text-center">{{ $ligne->quantite_facturee }}</td>
            <td class="product-cell">
                <div class="product-designation">
                    {{ $product->designation ?? '-' }}
                    @if ($product?->product_code)
                        — Réf '{{ $product->product_code }}'
                    @endif
                </div>
                @if ($product?->EAN)
                    <div class="product-meta">EAN : {{ $product->EAN }}</div>
                @endif
            </td>
            <td class="text-right">{{ number_format($ligne->prix_unitaire_ht, 2, ',', ' ') }}</td>
            <td class="text-right">{{ number_format($ligne->montant_final_ht, 2, ',', ' ') }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="4" class="text-center" style="font-style: normal; color: #9a9a9a; padding: 14px;">
                Aucune ligne de détail
            </td>
        </tr>
    @endforelse

    {{-- lignes vides pour respecter l'esthétique du modèle --}}
    @for ($i = 0; $i < max(0, 3 - $facture->detailFactures->count()); $i++)
        <tr class="empty-row">
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
    @endfor
    </tbody>
</table>

{{-- ============ Totaux ============ --}}
<table class="totals-wrapper">
    <tr>
        <td>
            <table class="totals-table">
                <tr>
                    <td>Total Hors Taxe</td>
                    <td class="totals-value">{{ number_format($facture->montant_ht, 2, ',', ' ') }} €</td>
                </tr>
                <tr>
                    <td>TVA à {{ rtrim(rtrim(number_format($facture->taux_tva, 2, ',', ' '), '0'), ',') }}%</td>
                    <td class="totals-value">{{ number_format($facture->montant_tva, 2, ',', ' ') }} €</td>
                </tr>
                <tr class="total-final box-bg">
                    <td>Total TTC en euros</td>
                    <td class="totals-value">{{ number_format($facture->montant_ttc, 2, ',', ' ') }} €</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ============ Formule de politesse ============ --}}
<div class="closing">
    En votre aimable règlement,<br>
    Cordialement,
</div>

{{-- ============ Pied de page ============ --}}
<div class="footer">
    Conditions de paiement : paiement à réception de facture, à 30 jours...<br>
    Aucun escompte consenti pour règlement anticipé<br>
    Tout incident de paiement est passible d'intérêt de retard. Le montant des pénalités résulte de l'application
    aux sommes restant dues d'un taux d'intérêt légal en vigueur au moment de l'incident.<br>
    Indemnité forfaitaire pour frais de recouvrement due au créancier en cas de retard de paiement : 40€

    <div class="legal">
        {{ isset($emetteur['siret']) ? $emetteur['siret'] : 'N° Siret 210.896.764 00015 RCS Montpellier' }}<br>
        {{ isset($emetteur['ape_tva']) ? $emetteur['ape_tva'] : 'Code APE 947A - N° TVA Intracom. FR 77825896764000' }}
    </div>
</div>

</body>
</html>
