<?php

namespace App\Filament\Resources\CaisseSessions\Pages;

use App\Filament\Resources\CaisseSessions\CaisseSessionResource;
use App\Models\CaisseSession;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Throwable;

class ViewCaisseSession extends ViewRecord
{
    protected static string $resource = CaisseSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn (CaisseSession $record): bool => $record->estOuverte()),

            Action::make('fermer')
                ->label('Fermer la session')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->visible(fn (CaisseSession $record): bool => $record->estOuverte())
                ->requiresConfirmation()
                ->modalHeading('Clôturer la session de caisse')
                ->modalDescription('Cette action fige la session : elle ne pourra plus être modifiée ensuite.')
                ->schema(function (CaisseSession $record): array {
                    // Solde théorique attendu : ouverture + ventes en caisse
                    // de la session. Sert de valeur suggérée, à confirmer ou
                    // corriger après comptage physique du tiroir-caisse.
                    $soldeCalcule = (float) $record->solde_ouverture
                        + (float) $record->ventes()->sum('montant_total_ht_vente');

                    return [
                        TextInput::make('solde_cloture_theorique')
                            ->label('Solde théorique')
                            ->numeric()
                            ->required()
                            ->default(round($soldeCalcule, 2))
                            ->helperText('Calculé automatiquement : solde d\'ouverture + ventes en caisse. Ajustez si besoin.'),

                        TextInput::make('solde_cloture_reel')
                            ->label('Solde réel (comptage physique)')
                            ->numeric()
                            ->required()
                            ->default(round($soldeCalcule, 2))
                            ->helperText('Prérempli avec le solde théorique — corrigez selon le comptage physique réel du tiroir-caisse.'),

                        Textarea::make('commentaire')
                            ->label('Commentaire')
                            ->placeholder('Ex : justification de l\'écart, remarques sur la journée...')
                            ->columnSpanFull(),
                    ];
                })
                ->action(function (CaisseSession $record, array $data) {
                    try {
                        $record->fermer(
                            soldeTheorique: (float) $data['solde_cloture_theorique'],
                            soldeReel: (float) $data['solde_cloture_reel'],
                            commentaire: $data['commentaire'] ?? null,
                        );
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Échec de la clôture')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    $ecart = (float) $data['solde_cloture_reel'] - (float) $data['solde_cloture_theorique'];

                    Notification::make()
                        ->title('Session clôturée')
                        ->body($ecart === 0.0
                            ? 'Aucun écart constaté.'
                            : 'Écart constaté : ' . number_format($ecart, 2) . ' MUR.')
                        ->color($ecart === 0.0 ? 'success' : 'warning')
                        ->send();

                    // Rafraîchit la page pour refléter le nouveau statut
                    // (statut, soldes, écart) sans navigation complète.
                    $this->redirect(static::getUrl(['record' => $record]));
                }),
        ];
    }
}
