<?php

namespace App\Filament\Resources\Commandes\Schemas;

use App\Livewire\ProductSelector;
use App\Models\Product;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class CommandeFormList
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make([

                    // ================= STEP 1 : Informations commande =================
                    Step::make('Informations')
                        ->icon(Heroicon::OutlinedClipboardDocumentList)
                        ->completedIcon(Heroicon::HandThumbUp)
                        ->schema([
                            TextInput::make('libelle')
                                ->required()
                                ->columnSpanFull(),

                            Select::make('fournisseur_id')
                                ->label('Fournisseur')
                                ->relationship('fournisseur', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('magasin_id')
                                ->label('Magasin')
                                ->relationship('magasin', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            TextInput::make('montant_minimum')
                                ->label('Montant minimum')
                                ->numeric(),

                            TextInput::make('remise_facture')
                                ->label('Remise facture')
                                ->numeric()
                                ->default(0)
                                ->required(),

                            TextInput::make('nombre_jours')
                                ->label('Délai (jours)')
                                ->numeric(),

                            Select::make('etat_commande')
                                ->label('État')
                                ->options([
                                    'pre_commande' => 'Pré-commande',
                                    'commande' => 'Commande',
                                ])
                                ->default('pre_commande')
                                ->required(),

                            Select::make('statut_commande')
                                ->label('Statut')
                                ->options([
                                    'annule' => 'Annulé',
                                    'cree' => 'Créé',
                                    'facturee' => 'Facturée',
                                    'cloturee' => 'Clôturée',
                                ])
                                ->default('cree')
                                ->required(),
                        ])
                        ->columns(2),

                    // ================= STEP 2 : Sélection produits via table + checkbox/modal =================
                    Step::make('Produits')
                        ->icon(Heroicon::OutlinedShoppingCart)
                        ->completedIcon(Heroicon::HandThumbUp)
                        ->schema([
                            Livewire::make(ProductSelector::class)
                                ->key('product-selector'),
                        ]),

                    // ================= STEP 3 : Résumé =================
                    Step::make('Résumé')
                        ->icon(Heroicon::OutlinedDocumentCheck)
                        ->completedIcon(Heroicon::HandThumbUp)
                        ->schema([
                            Placeholder::make('resume')
                                ->label('')
                                ->content(function (callable $get, $livewire) {
                                    $libelle = $get('libelle') ?? '-';
                                    $items = $livewire->wizardItems ?? [];

                                    $montantTotal = collect($items)->sum(function ($item) {
                                        $ht = (float) ($item['pu_achat_HT'] ?? 0);
                                        $tax = (float) ($item['tax'] ?? 0);
                                        $remise = (float) ($item['taux_remise'] ?? 0);
                                        $qty = (float) ($item['quantite'] ?? 0);
                                        $net = $ht + ($ht * $tax / 100) - ($ht * $remise / 100);

                                        return $net * $qty;
                                    });

                                    $lignes = collect($items)->map(function ($item) {
                                        $qty = $item['quantite'] ?? 0;
                                        $designation = $item['designation'] ?? '—';

                                        return '<li>' . e($designation) . ' — Qté: ' . e($qty) . '</li>';
                                    })->implode('');

                                    $nombreProduits = count($items);
                                    $montantFormate = number_format($montantTotal, 2);

                                    return new HtmlString(<<<HTML
                                        <div class="space-y-4">
                                            <div><strong>Libellé :</strong> {$libelle}</div>
                                            <div><strong>Nombre de produits :</strong> {$nombreProduits}</div>
                                            <ul class="list-disc list-inside">{$lignes}</ul>
                                            <div class="text-lg font-bold">Montant total estimé : {$montantFormate} MUR</div>
                                        </div>
                                    HTML);
                                }),
                        ]),

                ])
                    ->columnSpanFull()
                    ->skippable(false),
            ]);
    }
}
