{{-- resources/views/pdf/bon-commande.blade.php --}}
    <!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page { margin: 25px; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Jost', sans-serif;
            font-size: 10px;
            color: #333;
        }

        /* ===== TITLE ===== */
        .main-title {
            text-align: center;
            font-size: 15px;
            font-weight: 700;
            color: #2e5c8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 14px;
        }

        /* ===== HEADER (logo + titre) ===== */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .header-table td {
            border: none;
            vertical-align: middle;
        }
        .logo-cell {
            width: 50%;
            font-size: 16px;
            font-weight: 600;
            color: #9a9a9a;
        }
        .titre-cell {
            width: 50%;
            text-align: right;
            font-size: 24px;
            font-weight: 700;
            color: #2e5c8a;
        }

        /* ===== ADDRESS + META SECTION ===== */
        .top-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .top-table td {
            border: none;
            vertical-align: top;
            padding: 0;
        }
        .col-left {
            width: 55%;
            padding-right: 15px;
        }
        .col-right {
            width: 45%;
        }
        .section-label {
            font-weight: 700;
            color: #333;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .info-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .info-box td {
            border: 1px solid #b8cce4;
            padding: 4px 6px;
        }
        .info-box .field-label {
            background-color: #dce6f1;
            font-weight: 600;
            width: 45%;
            color: #2e5c8a;
        }
        .info-box .field-value {
            width: 55%;
            background-color: #fff;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            border: none;
            padding: 3px 2px;
        }
        .meta-table .meta-label {
            text-align: right;
            color: #2e5c8a;
            font-weight: 600;
            width: 55%;
        }
        .meta-table .meta-value {
            text-align: right;
            width: 45%;
            font-weight: 400;
        }

        /* ===== PRODUCTS TABLE ===== */
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .products-table th {
            background-color: #595959;
            color: #fff;
            border: 1px solid #595959;
            padding: 6px 5px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            text-align: left;
        }
        .products-table td {
            border: 1px solid #d9d9d9;
            padding: 5px;
            font-size: 9px;
            height: 16px;
        }
        .products-table .num { text-align: right; }
        .products-table .center { text-align: center; }
        .products-table tr:nth-child(even) td {
            background-color: #f2f2f2;
        }

        /* ===== REMARKS + TOTALS ===== */
        .bottom-section {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .bottom-section td {
            border: none;
            vertical-align: top;
        }
        .remarks-box {
            width: 55%;
            padding-right: 15px;
        }
        .remarks-content {
            border: 1px solid #b8cce4;
            background-color: #eaf1f8;
            min-height: 55px;
            padding: 6px;
            margin-top: 4px;
        }
        .totals-box {
            width: 45%;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 4px 6px;
            font-size: 9.5px;
        }
        .totals-table .t-label {
            text-align: right;
            color: #333;
        }
        .totals-table .t-value {
            text-align: right;
            width: 30%;
            border-bottom: 1px solid #d9d9d9;
        }
        .totals-table .final-row .t-label,
        .totals-table .final-row .t-value {
            font-weight: 700;
            font-size: 12px;
            color: #fff;
            background-color: #2e5c8a;
            border-bottom: none;
            padding: 6px;
        }

        .merci {
            font-weight: 700;
            font-size: 12px;
            color: #333;
            margin-top: 14px;
        }
        .cheque-note {
            font-size: 8.5px;
            color: #555;
            margin-top: 6px;
        }

        /* ===== FOOTER SHIPPING INFO ===== */
        .shipping-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        .shipping-table td {
            border: 1px solid #b8cce4;
            padding: 5px 8px;
            font-size: 9px;
        }
        .shipping-table .ship-label {
            background-color: #dce6f1;
            font-weight: 600;
            color: #2e5c8a;
            width: 30%;
        }
        .shipping-table .ship-value {
            width: 70%;
        }

        .page-footer {
            text-align: center;
            font-size: 8px;
            color: #777;
            margin-top: 14px;
        }
    </style>
</head>
<body>

<div class="main-title">Bon de commande — {{ config('app.name', 'ERP') }}</div>

{{-- ===== LOGO + TITRE ===== --}}
<table class="header-table">
    <tr>
        <td class="logo-cell">{{ config('app.name', 'ERP') }}</td>
        <td class="titre-cell">BON DE COMMANDE</td>
    </tr>
</table>

{{-- ===== NOM DE L'ENTREPRISE + META ===== --}}
<table class="top-table">
    <tr>
        <td class="col-left">
            <div class="section-label">Nom de l'entreprise</div>
            <table class="info-box">
                <tr>
                    <td class="field-label">Adresse</td>
                    <td class="field-value">{{ $bonCommande->magasinLivraison->adresse ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="field-label">Numéro de téléphone</td>
                    <td class="field-value">{{ $bonCommande->magasinLivraison->telephone ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="field-label">E-mail</td>
                    <td class="field-value">{{ $bonCommande->magasinLivraison->email ?? '-' }}</td>
                </tr>
            </table>
        </td>
        <td class="col-right">
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Date</td>
                    <td class="meta-value">{{ $bonCommande->date_commande?->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="meta-label">N° du bon de commande</td>
                    <td class="meta-value">{{ $bonCommande->numero }}</td>
                </tr>
                <tr>
                    <td class="meta-label">N° de compte</td>
                    <td class="meta-value">{{ $bonCommande->numero_compte ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Vendeur</td>
                    <td class="meta-value">{{ $commande->fournisseur->name }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Code fournisseur</td>
                    <td class="meta-value">{{ $bonCommande->code_fournisseur }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ===== FACTURER À (Fournisseur) / EXPÉDIER À (Magasin) ===== --}}
<table class="top-table">
    <tr>
        <td class="col-left">
            <div class="section-label">Facturer à</div>
            <table class="info-box">
                <tr>
                    <td class="field-label">Fournisseur</td>
                    <td class="field-value">{{ $commande->fournisseur->name }}</td>
                </tr>
                <tr>
                    <td class="field-label">Adresse</td>
                    <td class="field-value">
                        {{ $commande->fournisseur->adresse_siege ?? '-' }}<br>
                        {{ $commande->fournisseur->code_postal }} {{ $commande->fournisseur->ville }}
                    </td>
                </tr>
                <tr>
                    <td class="field-label">Téléphone</td>
                    <td class="field-value">{{ $commande->fournisseur->telephone ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="field-label">E-mail</td>
                    <td class="field-value">{{ $commande->fournisseur->email ?? '-' }}</td>
                </tr>
            </table>
        </td>
        <td class="col-right">
            <div class="section-label">Expédier à</div>
            <table class="info-box">
                <tr>
                    <td class="field-label">Magasin</td>
                    <td class="field-value">{{ $bonCommande->magasinLivraison->name }}</td>
                </tr>
                <tr>
                    <td class="field-label">Adresse</td>
                    <td class="field-value">{{ $bonCommande->magasinLivraison->adresse ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="field-label">Téléphone</td>
                    <td class="field-value">{{ $bonCommande->magasinLivraison->telephone ?? '-' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ===== PRODUCTS TABLE ===== --}}
<table class="products-table">
    <thead>
    <tr>
        <th style="width: 8%;">N°</th>
        <th style="width: 12%;">EAN</th>
        <th style="width: 32%;">Description</th>
        <th style="width: 10%;">Marque</th>
        <th style="width: 10%;">Qté</th>
        <th style="width: 14%;">Prix unitaire</th>
        <th style="width: 14%;">Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($commande->detailCommandes as $index => $detail)
        <tr>
            <td class="center">{{ $index + 1 }}</td>
            <td class="center">{{ $detail->product->EAN }}</td>
            <td>{{ $detail->product->designation }} <br><small>Réf. {{ $detail->product->product_code }}</small></td>
            <td>{{ $detail->product->marque?->name ?? '-' }}</td>
            <td class="center">{{ $detail->quantite }}</td>
            <td class="num">{{ number_format($detail->pu_achat_net, 2) }} MUR</td>
            <td class="num">{{ number_format($detail->pu_achat_net * $detail->quantite, 2) }} MUR</td>
        </tr>
    @endforeach
    </tbody>
</table>

{{-- ===== REMARQUES + TOTAUX ===== --}}
<table class="bottom-section">
    <tr>
        <td class="remarks-box">
            <div class="section-label">Remarques / Instructions</div>
            <div class="remarks-content">
                {{ $commande->libelle }}
            </div>
            <div class="cheque-note">
                Bon de commande généré automatiquement à partir de la commande n° {{ $commande->numero_commande }}.
            </div>
        </td>
        <td class="totals-box">
            <table class="totals-table">
                <tr>
                    <td class="t-label">Sous-total</td>
                    <td class="t-value">{{ number_format($commande->detailCommandes->sum(fn($d) => $d->pu_achat_HT * $d->quantite), 2) }} MUR</td>
                </tr>
                <tr>
                    <td class="t-label">Remise facture</td>
                    <td class="t-value">{{ number_format($commande->remise_facture, 2) }} %</td>
                </tr>
                <tr>
                    <td class="t-label">Taxe</td>
                    <td class="t-value">{{ number_format($commande->detailCommandes->sum(fn($d) => ($d->pu_achat_HT * $d->tax / 100) * $d->quantite), 2) }} MUR</td>
                </tr>
                <tr class="final-row">
                    <td class="t-label">TOTAL</td>
                    <td class="t-value">{{ number_format($bonCommande->montant_commande, 2) }} MUR</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="merci">Merci</div>

{{-- ===== SHIPPING / APPROVAL INFO ===== --}}
<table class="shipping-table">
    <tr>
        <td class="ship-label">Statut du bon de commande</td>
        <td class="ship-value">
            @php
                $statutLabels = ['annule' => 'Annulé', 'cree' => 'Créé', 'facturee' => 'Facturée', 'cloturee' => 'Clôturée'];
            @endphp
            {{ $statutLabels[$bonCommande->statut_commande] ?? $bonCommande->statut_commande }}
        </td>
    </tr>
    <tr>
        <td class="ship-label">Date de livraison souhaitée</td>
        <td class="ship-value">{{ $bonCommande->date_livraison?->format('d/m/Y') ?? '-' }}</td>
    </tr>
    <tr>
        <td class="ship-label">Commande approuvée par</td>
        <td class="ship-value">{{ $bonCommande->createdBy->name ?? '-' }}</td>
    </tr>
    <tr>
        <td class="ship-label">Signature</td>
        <td class="ship-value">&nbsp;</td>
    </tr>
    <tr>
        <td class="ship-label">Date</td>
        <td class="ship-value">{{ now()->format('d/m/Y') }}</td>
    </tr>
</table>

<div class="page-footer">
    Pour toute question concernant cette commande, veuillez contacter {{ $bonCommande->createdBy->name ?? 'notre service achats' }}.
    Généré le {{ now()->locale('fr')->translatedFormat('d F Y \à H:i') }}
</div>

</body>
</html>
