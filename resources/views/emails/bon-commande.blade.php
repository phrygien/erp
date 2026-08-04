<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; color: #333; font-size: 14px;">
<p>Bonjour {{ $commande->fournisseur->name }},</p>

<p>
    Veuillez trouver ci-joint notre bon de commande
    n° <strong>{{ $bonCommande->numero }}</strong>
    (commande {{ $commande->numero_commande }} — {{ $commande->libelle }}).
</p>

<table style="border-collapse: collapse; margin: 15px 0;">
    <tr>
        <td style="padding: 4px 10px 4px 0; font-weight: bold;">Date de commande :</td>
        <td style="padding: 4px 0;">{{ $bonCommande->date_commande?->format('d/m/Y') }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 10px 4px 0; font-weight: bold;">Livraison souhaitée :</td>
        <td style="padding: 4px 0;">{{ $bonCommande->date_livraison?->format('d/m/Y') ?? 'À convenir' }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 10px 4px 0; font-weight: bold;">Magasin de livraison :</td>
        <td style="padding: 4px 0;">{{ $bonCommande->magasinLivraison->name }}</td>
    </tr>
    <tr>
        <td style="padding: 4px 10px 4px 0; font-weight: bold;">Montant total :</td>
        <td style="padding: 4px 0;">{{ number_format($bonCommande->montant_commande, 2) }} MUR</td>
    </tr>
</table>

<p>Merci de bien vouloir confirmer la réception de ce bon de commande.</p>

<p>Cordialement,<br>{{ config('app.name') }}</p>
</body>
</html>
