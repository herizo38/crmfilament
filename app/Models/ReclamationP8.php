<?php

namespace App\Models;

use App\Enums\StatutReclamation;
use App\Enums\TicketStatut;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ReclamationP8 extends Model
{
    protected $table = 'reclamation_p8s';

    protected $casts = [
        'statut' => StatutReclamation::class,  // ✅ Typage enum
        'date_ouverture' => 'datetime',
        'date_resolution_cible' => 'date',
        'date_resolution_effective' => 'date',
        'validation_superviseur' => 'boolean',
    ];

    protected $fillable = [
        'ticket_id',
        'rapport_satisfaction_id',
        'date_ouverture',
        'description_reclamation',
        'statut',
        'date_resolution_cible',
        'date_resolution_effective',
        'validation_superviseur',
        'superviseur_id',
        'notes_resolution',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getStatutLabelAttribute(): string
    {
        return $this->statut->label();
    }

    public function getStatutColorAttribute(): string
    {
        return $this->statut->color();
    }

    public function getStatutIconAttribute(): string
    {
        return $this->statut->icon();
    }

    public function getDelaiRestantJoursAttribute(): int
    {
        if ($this->estCloturee()) {
            return 0;
        }
        return (int) now()->startOfDay()->diffInDays($this->date_resolution_cible, false);
    }

    public function getDelaiRestantFormateAttribute(): string
    {
        $jours = $this->delai_restant_jours;

        if ($this->estCloturee()) {
            return 'Clôturée';
        }

        if ($jours < 0) {
            return abs($jours) . ' jour(s) de retard';
        }

        if ($jours === 0) {
            return "Aujourd'hui";
        }

        return "J-{$jours}";
    }

    public function getDelaiOuvertureHeuresAttribute(): float
    {
        return $this->date_ouverture->diffInHours($this->created_at);
    }

    public function getDelaiResolutionJoursAttribute(): ?int
    {
        if (!$this->date_resolution_effective) {
            return null;
        }
        return $this->date_ouverture->diffInDays($this->date_resolution_effective);
    }

    public function getUrgenceLevelAttribute(): string
    {
        if ($this->estCloturee()) {
            return 'success';
        }

        $joursRestants = $this->delai_restant_jours;

        return match (true) {
            $joursRestants < 0 => 'danger',      // En retard
            $joursRestants <= 1 => 'warning',    // Urgent
            $joursRestants <= 3 => 'info',       // À surveiller
            default => 'success',                // Dans les temps
        };
    }

    public function getEstDansLesTempsAttribute(): bool
    {
        return !$this->estCloturee() && $this->delai_restant_jours >= 0;
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function estOuverte(): bool
    {
        return $this->statut === StatutReclamation::Ouverte;
    }

    public function estEnTraitement(): bool
    {
        return $this->statut === StatutReclamation::EnTraitement;
    }

    public function estValideeSuperviseur(): bool
    {
        return $this->statut === StatutReclamation::ValideeSuperviseur;
    }

    public function estCloturee(): bool
    {
        return $this->statut === StatutReclamation::Cloturee;
    }

    public function estActive(): bool  // ✅ Des deux modèles
    {
        return $this->statut->estActive();
    }

    public function estEnRetard(): bool  // ✅ Des deux modèles (amélioré)
    {
        return $this->estActive() &&
            now()->startOfDay()->gt($this->date_resolution_cible);
    }

    public function peutPasserA(StatutReclamation $nouveauStatut): bool  // ✅ Du modèle 2
    {
        return in_array($nouveauStatut, $this->statut->statutsSuivants());
    }

    public function changerStatut(StatutReclamation $nouveauStatut, ?string $notes = null): void
    {
        if (!$this->peutPasserA($nouveauStatut)) {
            throw new \Exception(
                "Transition impossible de {$this->statut->value} à {$nouveauStatut->value}"
            );
        }

        $data = ['statut' => $nouveauStatut];

        if ($notes) {
            $data['notes_resolution'] = $this->notes_resolution
                ? $this->notes_resolution . "\n[" . now()->format('d/m/Y H:i') . "] {$notes}"
                : $notes;
        }

        // Si clôture, enregistrer la date
        if ($nouveauStatut === StatutReclamation::Cloturee && !$this->date_resolution_effective) {
            $data['date_resolution_effective'] = now();
        }

        $this->update($data);
    }

    public function validerParSuperviseur(User $superviseur, ?string $notes = null): void
    {
        if (!$this->estEnTraitement()) {
            throw new \Exception("La réclamation doit être en traitement pour être validée");
        }

        $data = [
            'statut' => StatutReclamation::ValideeSuperviseur,
            'validation_superviseur' => true,
            'superviseur_id' => $superviseur->id,
        ];

        if ($notes) {
            $data['notes_resolution'] = $this->notes_resolution
                ? $this->notes_resolution . "\n[Superviseur - " . now()->format('d/m/Y H:i') . "] {$notes}"
                : "[Superviseur - " . now()->format('d/m/Y H:i') . "] {$notes}";
        }

        $this->update($data);
    }

    // ── Dans la méthode cloturer() ──
    public function cloturer(?string $notes = null): void
    {
        $this->changerStatut(StatutReclamation::Cloturee, $notes);

        if ($this->ticket) {
            $this->ticket->cloturer("Réclamation P8 clôturée");
        }
    }

    public function mettreEnTraitement(?string $notes = null): void
    {
        $this->changerStatut(StatutReclamation::EnTraitement, $notes);
    }

    public function getDureeRestanteAvantRetard(): string
    {
        if ($this->estCloturee()) {
            return 'Clôturée';
        }

        if ($this->estEnRetard()) {
            $joursRetard = abs($this->delai_restant_jours);
            return "En retard de {$joursRetard} jour(s)";
        }

        return "J-{$this->delai_restant_jours}";
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeActives($query)
    {
        return $query->whereIn('statut', [
            StatutReclamation::Ouverte->value,
            StatutReclamation::EnTraitement->value,
            StatutReclamation::ValideeSuperviseur->value,
        ]);
    }

    public function scopeEnRetard($query)
    {
        return $query->whereIn('statut', [
            StatutReclamation::Ouverte->value,
            StatutReclamation::EnTraitement->value,
        ])->whereDate('date_resolution_cible', '<', now());
    }

    public function scopeOuvertes($query)
    {
        return $query->where('statut', StatutReclamation::Ouverte);
    }

    public function scopeEnTraitement($query)
    {
        return $query->where('statut', StatutReclamation::EnTraitement);
    }

    public function scopeCloturees($query)
    {
        return $query->where('statut', StatutReclamation::Cloturee);
    }

    public function scopeSansSuperviseur($query)
    {
        return $query->where('validation_superviseur', false)
            ->where('statut', StatutReclamation::EnTraitement);
    }

    public function scopeAValider($query)
    {
        return $query->where('statut', StatutReclamation::EnTraitement)
            ->where('validation_superviseur', false);
    }

    public function scopeDuMois($query)
    {
        return $query->whereMonth('date_ouverture', now()->month)
            ->whereYear('date_ouverture', now()->year);
    }

    // ── Méthodes statiques KPIs ─────────────────────────────────────
    public static function getKpis(): array
    {
        return [
            'total_actives' => static::actives()->count(),
            'ouvertes' => static::ouvertes()->count(),
            'en_traitement' => static::enTraitement()->count(),
            'en_retard' => static::enRetard()->count(),
            'a_valider' => static::aValider()->count(),
            'cloturees_mois' => static::cloturees()->duMois()->count(),
            'delai_moyen_resolution' => static::getDelaiMoyenResolution(),
            'taux_resolution_sla' => static::getTauxResolutionSLA(),
        ];
    }

    public static function getDelaiMoyenResolution(): float
    {
        return round(
            static::cloturees()
                ->whereNotNull('date_resolution_effective')
                ->get()
                ->avg(fn($r) => $r->date_ouverture->diffInDays($r->date_resolution_effective)) ?? 0,
            1
        );
    }

    public static function getTauxResolutionSLA(): float
    {
        $total = static::cloturees()->count();
        if ($total === 0) return 100;

        $dansLesTemps = static::cloturees()
            ->whereNotNull('date_resolution_effective')
            ->whereColumn('date_resolution_effective', '<=', 'date_resolution_cible')
            ->count();

        return round(($dansLesTemps / $total) * 100, 1);
    }

    // ── Calcul date résolution ──────────────────────────────────────
    public static function calculerDateResolutionCible(Carbon $dateOuverture): Carbon  // ✅ Du modèle 2
    {
        $date = $dateOuverture->copy();
        $joursAjoutes = 0;

        while ($joursAjoutes < 5) {
            $date->addDay();
            if (!$date->isWeekend()) {
                $joursAjoutes++;
            }
        }

        return $date;
    }

    // ── Boot ────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (ReclamationP8 $reclamation) {
            // ✅ Du modèle 2 : Dates auto
            if (!$reclamation->date_ouverture) {
                $reclamation->date_ouverture = now();
            }

            if (!$reclamation->date_resolution_cible) {
                $reclamation->date_resolution_cible = static::calculerDateResolutionCible(
                    $reclamation->date_ouverture
                );
            }

            if (!$reclamation->statut) {
                $reclamation->statut = StatutReclamation::Ouverte;
            }
        });

        static::updating(function (ReclamationP8 $reclamation) {
            // ✅ Du modèle 2 : Auto-date clôture
            if (
                $reclamation->isDirty('statut') &&
                $reclamation->statut === StatutReclamation::Cloturee &&
                !$reclamation->date_resolution_effective
            ) {
                $reclamation->date_resolution_effective = now();
            }

            // ✅ Du modèle 2 : Auto-validation superviseur
            if (
                $reclamation->isDirty('validation_superviseur') &&
                $reclamation->validation_superviseur &&
                $reclamation->statut === StatutReclamation::EnTraitement
            ) {
                $reclamation->statut = StatutReclamation::ValideeSuperviseur;
            }

            // ✅ Nouveau : Si clôture, mettre à jour le ticket
            if (
                $reclamation->isDirty('statut') &&
                $reclamation->statut === StatutReclamation::Cloturee &&
                $reclamation->ticket
            ) {

                // Vérifier si d'autres réclamations sont actives
                $autresActives = $reclamation->ticket->reclamations()
                    ->where('id', '!=', $reclamation->id)
                    ->whereIn('statut', [
                        StatutReclamation::Ouverte->value,
                        StatutReclamation::EnTraitement->value,
                    ])
                    ->exists();

                // ── Dans booted() updating ──
                if (!$autresActives) {
                    $reclamation->ticket->cloturer(
                        "Toutes les réclamations P8 sont clôturées"
                    );
                }
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function rapportSatisfaction()  // ✅ Du modèle 2
    {
        return $this->belongsTo(RapportSatisfactionP6::class, 'rapport_satisfaction_id');
    }

    public function superviseur()
    {
        return $this->belongsTo(User::class, 'superviseur_id');
    }
}
