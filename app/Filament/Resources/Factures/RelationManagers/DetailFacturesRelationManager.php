<?php

namespace App\Filament\Resources\Factures\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DetailFacturesRelationManager extends RelationManager
{
    protected static string $relationship = 'detailFactures';

    protected static ?string $title = 'Détails de la facture';

    protected static ?string $modelLabel = 'ligne';

    protected static ?string $pluralModelLabel = 'lignes';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('detail_commande_id')
                    ->label('Ligne de commande')
                    ->relationship('detailCommande', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->product?->designation
                        ? "{$record->product->designation} (qté cmd: {$record->quantite})"
                        : "#{$record->id}")
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('quantite_commande')
                    ->label('Quantité commandée')
                    ->numeric()
                    ->required(),

                TextInput::make('quantite_facturee')
                    ->label('Quantité facturée')
                    ->numeric()
                    ->required(),

                TextInput::make('prix_unitaire_ht')
                    ->label('Prix unitaire HT')
                    ->numeric()
                    ->prefix('€')
                    ->required(),

                TextInput::make('montant_remise')
                    ->label('Remise')
                    ->numeric()
                    ->prefix('€')
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('detailCommande.product.designation')
                    ->label('Produit')
                    ->description(fn ($record) => $record->detailCommande?->product?->product_code)
                    ->searchable(),

                TextColumn::make('quantite_commande')
                    ->label('Qté commandée')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('quantite_facturee')
                    ->label('Qté facturée')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($record) => $record->aEcartQuantite() ? 'warning' : null),

                TextColumn::make('prix_unitaire_ht')
                    ->label('P.U. HT')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('montant_ht')
                    ->label('Montant HT')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('montant_remise')
                    ->label('Remise')
                    ->money('EUR')
                    ->color('danger'),

                TextColumn::make('montant_final_ht')
                    ->label('Total HT')
                    ->money('EUR')
                    ->weight('bold'),

                TextColumn::make('montant_final_net')
                    ->label('Total net')
                    ->money('EUR')
                    ->weight('bold')
                    ->color('primary'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
