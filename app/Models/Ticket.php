<?php

namespace App\Models;

use App\Enums\TicketStatut;
use App\Enums\NiveauPriorite;
use App\Enums\CorpsDeMetier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference',
        'contact_particulier_id',
        'artisan_id',
        'operateur_id',
        'statut',
        'niveau_priorite',
        'corps_de_metier',
        'date_creation',
        'date_cloture',
        'rdv_planifie_at',
        'rappel_promise_at',
        'aircall_call_id',
        'source_appel',        // NOUVEAU : Via CTI / téléphonie
        'notes',
    ];

    /**
     * Typage strict des attributs.
     * En liant les Enums ici, Laravel s'occupe de toutes les conversions.
     */
    protected $casts = [
        'statut'           => TicketStatut::class,
        'niveau_priorite'  => NiveauPriorite::class,
        'corps_de_metier'  => CorpsDeMetier::class,
        'date_creation'    => 'datetime',
        'date_cloture'     => 'datetime',
        'rdv_planifie_at'  => 'datetime',
        'rappel_promise_at'=> 'datetime',
    ];

    // ── Accesseurs Nettoyés (Grâce au Cast d'Enum) ───────────────────

    public function getStatutLabelAttribute(): string
    {
        return $this->statut?->label() ?? 'Non défini';
    }

    public function getStatutColorAttribute(): string
    {
        return $this->statut?->color() ?? 'gray';
    }

    public function getStatutIconAttribute(): string
    {
        return $this->statut?->icon() ?? 'heroicon-o-question-mark-circle';
    }

    public function getPrioriteLabelAttribute(): string
    {
        return $this->niveau_priorite?->label() ?? 'Non défini';
    }

    public function getPrioriteColorAttribute(): string
    {
        return $this->niveau_priorite?->color() ?? 'gray';
    }

    public function getDureeTraitementMinutesAttribute(): int
    {
        $fin = $this->date_cloture ?? now();
        return $this->date_creation ? $fin->diffInMinutes($this->date_creation) : 0;
    }

    public function getDureeTraitementFormateeAttribute(): string
    {
        $minutes = $this->duree_traitement_minutes;

        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $heures = floor($minutes / 60);
        $mins   = $minutes % 60;

        if ($heures < 24) {
            return $heures . 'h' . ($mins > 0 ? ' ' . $mins . 'min' : '');
        }

        $jours  = floor($heures / 24);
        $heures = $heures % 24;
        return $jours . 'j ' . $heures . 'h';
    }

    public function getSlaRespecteAttribute(): bool
    {
        if (!$this->niveau_priorite || !$this->artisan_id) {
            return true;
        }

        $delaiMax = $this->niveau_priorite->delaiMaxMinutes() ?? 480;
        return $this->duree_traitement_minutes <= $delaiMax;
    }

    public function getEstEnRetardAttribute(): bool
    {
        return !$this->sla_respecte && ($this->statut?->estActif() ?? false);
    }

    public function getStatutOrdreAttribute(): int
    {
        return $this->statut?->ordre() ?? 0;
    }

    public function getProgressionPourcentageAttribute(): int
    {
        return (int) round((($this->statut?->ordre() ?? 0) / 14) * 100);
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeActifs($query): Builder
    {
        return $query->whereNotIn('statut', [
            TicketStatut::DossierCloture->value,
            TicketStatut::ClotureSatisfait->value,
        ]);
    }

    public function scopeClotures($query): Builder
    {
        return $query->whereIn('statut', [
            TicketStatut::DossierCloture->value,
            TicketStatut::ClotureSatisfait->value,
        ]);
    }

    public function scopeBloquants($query): Builder
    {
        return $query->whereIn('statut', [
            TicketStatut::FicheIncomplete->value,
            TicketStatut::ReclamationOuverte->value,
            TicketStatut::SuiviQualiteRequis->value,
        ]);
    }

    public function scopeByStatut($query, TicketStatut $statut): Builder
    {
        return $query->where('statut', $statut->value);
    }

    public function scopeByPriorite($query, NiveauPriorite $priorite): Builder
    {
        return $query->where('niveau_priorite', $priorite->value);
    }

    public function scopeUrgents($query): Builder
    {
        return $query->where('niveau_priorite', NiveauPriorite::Urgence->value)
                     ->whereNotIn('statut', [
                         TicketStatut::DossierCloture->value,
                         TicketStatut::ClotureSatisfait->value,
                     ]);
    }

    public function scopeEnRetard($query): Builder
    {
        return $query->where(function ($q) {
            $q->where(function ($q) {
                    $q->where('niveau_priorite', NiveauPriorite::Urgence->value)
                      ->where('date_creation', '<', now()->subMinutes(30));
                })
                ->orWhere(function ($q) {
                    $q->where('niveau_priorite', NiveauPriorite::Prioritaire->value)
                      ->where('date_creation', '<', now()->subMinutes(120));
                })
                ->orWhere(function ($q) {
                    $q->where('niveau_priorite', NiveauPriorite::Standard->value)
                      ->where('date_creation', '<', now()->subMinutes(480));
                });
        })->whereNotIn('statut', [
            TicketStatut::DossierCloture->value,
            TicketStatut::ClotureSatisfait->value,
        ]);
    }

    public function scopeSansArtisan($query): Builder
    {
        return $query->whereNull('artisan_id')
                     ->whereIn('statut', [
                         TicketStatut::FicheComplete->value,
                         TicketStatut::RdvPlanifie->value,
                     ]);
    }

    public function scopeDuJour($query): Builder
    {
        return $query->whereDate('date_creation', today());
    }

    public function scopeDeLaSemaine($query): Builder
    {
        return $query->whereBetween('date_creation', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeParSourceAppel($query, string $source): Builder
    {
        return $query->where('source_appel', $source);
    }

    // ── Méthodes métier ─────────────────────────────────────────────

    public function estActif(): bool
    {
        return $this->statut?->estActif() ?? false;
    }

    public function estBloquant(): bool
    {
        return $this->statut?->estBloquant() ?? false;
    }

    public function estCloture(): bool
    {
        return in_array($this->statut, [
            TicketStatut::DossierCloture,
            TicketStatut::ClotureSatisfait,
        ], true);
    }

    public function peutPasserA(TicketStatut $nouveauStatut): bool
    {
        return in_array($nouveauStatut, $this->statut?->statutsSuivants() ?? [], true);
    }

    public function changerStatut(TicketStatut $nouveauStatut, ?string $notes = null): void
    {
        if (!$this->peutPasserA($nouveauStatut)) {
            throw new \Exception("Transition impossible de {$this->statut?->value} à {$nouveauStatut->value}");
        }

        $data = ['statut' => $nouveauStatut->value];

        if ($notes) {
            $data['notes'] = $this->notes . "\n[" . now()->format('d/m/Y H:i') . "] {$notes}";
        }

        if (in_array($nouveauStatut, [TicketStatut::DossierCloture, TicketStatut::ClotureSatisfait], true)) {
            $data['date_cloture'] = now();
        }

        $this->update($data);
    }

    public function assignerArtisan(Artisan $artisan): void
    {
        if (!$artisan->estDisponible()) {
            throw new \Exception("L'artisan n'est pas disponible");
        }

        $this->update(['artisan_id' => $artisan->id]);
    }

    public function planifierRDV(\DateTime $dateRdv): void
    {
        $this->update([
            'statut'          => TicketStatut::RdvPlanifie->value,
            'rdv_planifie_at'=> $dateRdv,
        ]);
    }

    public function programmerRappel(\DateTime $dateRappel): void
    {
        $this->update([
            'statut'            => TicketStatut::RappelPromis->value,
            'rappel_promise_at'=> $dateRappel,
        ]);
    }

    public function completeterFiche(array $data): FicheP2
    {
        $fiche = $this->ficheP2()->create($data);

        if ($fiche->fiche_complete) {
            $this->changerStatut(TicketStatut::FicheComplete, 'Fiche P2 complétée');
        } else {
            $this->changerStatut(TicketStatut::FicheIncomplete, 'Fiche P2 incomplète');
        }

        return $fiche;
    }

    public function necessiteP8(): bool
    {
        return $this->rapportSatisfaction && $this->rapportSatisfaction->note_nps <= 5;
    }

    public function getDelaiRestantSLA(): int
    {
        if (!$this->niveau_priorite || $this->estCloture()) {
            return 0;
        }

        $delaiMax = $this->niveau_priorite->delaiMaxMinutes() ?? 480;
        $ecoule   = $this->date_creation ? $this->date_creation->diffInMinutes(now()) : 0;

        return max(0, $delaiMax - $ecoule);
    }

    public function getSlaDepasseDepuis(): ?string
    {
        if ($this->sla_respecte || !$this->date_creation || !$this->niveau_priorite) {
            return null;
        }

        $delaiMax    = $this->niveau_priorite->delaiMaxMinutes() ?? 480;
        $depassement = $this->date_creation->addMinutes($delaiMax);

        return $depassement->diffForHumans();
    }

    /**
     * Vérifie si l'intervention a été réalisée (statut InterventionRealisee)
     */
    public function estInterventionRealisee(): bool
    {
        return $this->statut === TicketStatut::InterventionRealisee;
    }

    /**
     * Passe le ticket en statut "Intervention réalisée"
     * (statut intermédiaire entre ArtisanConfirme et ClotureSatisfait)
     */
    public function validerInterventionRealisee(): void
    {
        if ($this->statut === TicketStatut::ArtisanConfirme) {
            $this->changerStatut(
                TicketStatut::InterventionRealisee,
                'Intervention réalisée par l\'artisan'
            );
        } else {
            throw new \Exception(
                "Impossible de passer au statut InterventionRealisee depuis le statut {$this->statut?->value}"
            );
        }
    }

    // ── Méthodes statiques calculées côté SQL ────────────────────────

    public static function genererReference(): string
    {
        $count = static::whereYear('created_at', now()->year)->count() + 1;
        return 'TK-' . now()->year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    public static function getKpis(): array
    {
        return [
            'total_jour'          => static::duJour()->count(),
            'actifs'              => static::actifs()->count(),
            'urgents'             => static::urgents()->count(),
            'en_retard'           => static::enRetard()->count(),
            'sans_artisan'        => static::sansArtisan()->count(),
            'clotures_jour'       => static::clotures()->duJour()->count(),
            'taux_satisfaction'   => static::getTauxSatisfaction(),
            'delai_moyen_minutes' => static::getDelaiMoyen(),
        ];
    }

    public static function getTauxSatisfaction(): float
    {
        $total = static::clotures()->whereHas('rapportSatisfaction')->count();
        if ($total === 0) return 0;

        $satisfaits = static::clotures()
            ->whereHas('rapportSatisfaction', function ($q) {
                $q->where('note_nps', '>=', 8);
            })->count();

        return round(($satisfaits / $total) * 100, 1);
    }

    /**
     * Optimisation majeure : Calcul direct par la base de données (Évite l'implosion mémoire)
     */
    public static function getDelaiMoyen(): float
    {
        return (float) static::clotures()
            ->whereNotNull('date_cloture')
            ->whereNotNull('date_creation')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, date_creation, date_cloture)) as average_duration'))
            ->value('average_duration') ?? 0.0;
    }

    // ── Boot ────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (empty($ticket->reference)) {
                $ticket->reference = static::genererReference();
            }
            if (empty($ticket->date_creation)) {
                $ticket->date_creation = now();
            }
            if (empty($ticket->statut)) {
                $ticket->statut = TicketStatut::AppelRecu;
            }
        });

        static::updating(function (Ticket $ticket) {
            if ($ticket->isDirty('statut') &&
                $ticket->estCloture() &&
                !$ticket->date_cloture) {
                $ticket->date_cloture = now();
            }
        });
    }

    // ── Relations Typées (Sécurise Filament v3) ──────────────────────

    public function contactParticulier(): BelongsTo
    {
        return $this->belongsTo(ContactParticulier::class);
    }

    public function artisan(): BelongsTo
    {
        return $this->belongsTo(Artisan::class);
    }

    public function operateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operateur_id');
    }

    public function ficheP2(): HasOne
    {
        return $this->hasOne(FicheP2::class);
    }

    public function rapportsSatisfaction(): HasMany
    {
        return $this->hasMany(RapportSatisfactionP6::class);
    }

    public function rapportSatisfaction(): HasOne
    {
        return $this->hasOne(RapportSatisfactionP6::class)->latestOfMany();
    }

    public function reclamations(): HasMany
    {
        return $this->hasMany(ReclamationP8::class);
    }

    public function reclamation(): HasOne
    {
        return $this->hasOne(ReclamationP8::class)->latestOfMany();
    }

    public function reclamationActive(): HasOne
    {
        return $this->hasOne(ReclamationP8::class)
            ->whereIn('statut', ['ouverte', 'en_traitement']);
    }

    // ── NOUVELLES RELATIONS (Devis, Bons de commande, Factures) ──────

    /**
     * Relation avec les devis (un ticket peut avoir plusieurs devis)
     */
    public function devis(): HasMany
    {
        return $this->hasMany(Devis::class);
    }

    /**
     * Relation avec le bon de commande principal (le plus récent)
     */
    public function bonDeCommande(): HasOne
    {
        return $this->hasOne(BonDeCommande::class)->latestOfMany();
    }

    /**
     * Relation avec tous les bons de commande
     */
    public function bonsDeCommande(): HasMany
    {
        return $this->hasMany(BonDeCommande::class);
    }

    /**
     * Relation avec la facture principale (la plus récente)
     */
    public function facture(): HasOne
    {
        return $this->hasOne(Facture::class)->latestOfMany();
    }

    /**
     * Relation avec toutes les factures
     */
    public function factures(): HasMany
    {
        return $this->hasMany(Facture::class);
    }
}
