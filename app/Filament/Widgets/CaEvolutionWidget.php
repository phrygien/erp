<?php

namespace App\Filament\Widgets;

use App\Models\Vente;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CaEvolutionWidget extends ChartWidget
{
    protected ?string $heading = "Évolution du chiffre d'affaires";

    // Demi-largeur pour un affichage côte à côte avec CaParMagasinWidget
    // sur une grille dashboard à 2 colonnes.
    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    public ?string $filter = 'mois';

    protected function getFilters(): ?array
    {
        return [
            'jour' => "Aujourd'hui (par heure)",
            'semaine' => 'Cette semaine (par jour)',
            'mois' => 'Ce mois-ci (par jour)',
            'annee' => 'Cette année (par mois)',
        ];
    }

    protected function getData(): array
    {
        $periode = $this->filter ?? 'mois';

        [$labels, $valeurs] = match ($periode) {
            'jour' => $this->donneesParHeure(),
            'semaine' => $this->donneesParJour(Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()),
            'annee' => $this->donneesParMois(),
            default => $this->donneesParJour(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()),
        };

        return [
            'datasets' => [
                [
                    'label' => "Chiffre d'affaires (MUR)",
                    'data' => $valeurs,
                    'borderColor' => '#22c55e',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * NB : on évite volontairement les fonctions SQL type HOUR()/MONTH(),
     * spécifiques à MySQL et absentes de SQLite (utilisé en local/dev).
     * On récupère les lignes brutes de la période puis on les regroupe
     * en PHP avec Carbon — ça reste rapide sur des volumes de ventes
     * raisonnables et ça fonctionne à l'identique sur SQLite, MySQL,
     * PostgreSQL, etc.
     */

    /**
     * Répartition heure par heure pour la journée en cours (0h à 23h).
     */
    protected function donneesParHeure(): array
    {
        $ventes = Vente::query()
            ->whereBetween('created_at', [Carbon::today(), Carbon::now()->endOfDay()])
            ->get(['created_at', 'montant_total']);

        $totauxParHeure = $ventes
            ->groupBy(fn (Vente $vente) => Carbon::parse($vente->created_at)->format('H'))
            ->map(fn ($groupe) => (float) $groupe->sum('montant_total'));

        $labels = [];
        $valeurs = [];

        foreach (range(0, 23) as $heure) {
            $cle = sprintf('%02d', $heure);
            $labels[] = "{$cle}:00";
            $valeurs[] = (float) ($totauxParHeure[$cle] ?? 0);
        }

        return [$labels, $valeurs];
    }

    /**
     * Répartition jour par jour sur un intervalle donné (utilisé pour
     * "semaine" et "mois"). Chaque jour de l'intervalle apparaît, même à
     * zéro, pour éviter les trous dans le graphique.
     */
    protected function donneesParJour(Carbon $debut, Carbon $fin): array
    {
        $ventes = Vente::query()
            ->whereBetween('created_at', [$debut, $fin])
            ->get(['created_at', 'montant_total']);

        $totauxParJour = $ventes
            ->groupBy(fn (Vente $vente) => Carbon::parse($vente->created_at)->format('Y-m-d'))
            ->map(fn ($groupe) => (float) $groupe->sum('montant_total'));

        $labels = [];
        $valeurs = [];

        $curseur = $debut->copy()->startOfDay();

        while ($curseur->lte($fin)) {
            $cle = $curseur->format('Y-m-d');
            $labels[] = $curseur->format('d/m');
            $valeurs[] = (float) ($totauxParJour[$cle] ?? 0);
            $curseur->addDay();
        }

        return [$labels, $valeurs];
    }

    /**
     * Répartition mois par mois sur l'année en cours (janvier à décembre).
     */
    protected function donneesParMois(): array
    {
        $ventes = Vente::query()
            ->whereBetween('created_at', [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()])
            ->get(['created_at', 'montant_total']);

        $totauxParMois = $ventes
            ->groupBy(fn (Vente $vente) => Carbon::parse($vente->created_at)->format('n'))
            ->map(fn ($groupe) => (float) $groupe->sum('montant_total'));

        $nomsMois = [
            1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Août',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc',
        ];

        $labels = [];
        $valeurs = [];

        foreach (range(1, 12) as $mois) {
            $labels[] = $nomsMois[$mois];
            $valeurs[] = (float) ($totauxParMois[(string) $mois] ?? 0);
        }

        return [$labels, $valeurs];
    }
}
