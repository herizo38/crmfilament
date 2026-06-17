<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Opportunite extends Model
{
    use SoftDeletes;

    protected $table = 'opportunites';

    protected $casts = [
        'date_detection' => 'date',
        'date_premier_contact' => 'date',
        'nb_salaries' => 'integer',
        'chiffre_affaires' => 'decimal:2',
    ];

    protected $fillable = [
        'nom_entite',
        'type_pressenti',
        'departement',
        'telephone',
        'email',
        'adresse',
        'siret',
        'secteur_activite',
        'nb_salaries',
        'chiffre_affaires',
        'source_detection',
        'details_source',
        'potentiel',
        'statut',
        'interlocuteur_nom',
        'interlocuteur_fonction',
        'interlocuteur_telephone',
        'interlocuteur_email',
        'assigne_a',
        'date_detection',
        'date_premier_contact',
        'notes',
        'raison_perte',
        'converti_en_prospect_id',
    ];

    // ── Constantes ──────────────────────────────────────────────────
    // Statuts alignés sur le CDC §4.3 (Nouveau / En cours d'évaluation / Qualifiée / Converti / Perdue).
    const STATUTS = [
        'nouveau' => 'Nouveau',
        'en_qualification' => "En cours d'évaluation",
        'contacte' => 'Contacté',
        'rdv_planifie' => 'RDV planifié',
        'en_negociation' => 'En négociation',
        'qualifiee' => 'Qualifiée',
        'converti' => 'Converti',
        'perdu' => 'Perdu',
    ];

    const POTENTIELS = [
        'faible' => 'Faible',
        'moyen' => 'Moyen',
        'eleve' => 'Élevé',
        'tres_eleve' => 'Très élevé',
    ];

    // Sources de détection alignées sur le CDC §4.3.
    const SOURCES = [
        'reseau_commercial' => 'Réseau commercial',
        'client_existant' => 'Client existant',
        'parrainage' => 'Parrainage',
        'phoning_entrant' => 'Phoning entrant',
        'salon' => 'Salon',
        'linkedin' => 'LinkedIn',
        'fichier_externe' => 'Fichier externe',
        'autre' => 'Autre',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getStatutColorAttribute(): string
    {
        return match($this->statut) {
            'nouveau' => 'info',
            'en_qualification' => 'warning',
            'contacte' => 'primary',
            'rdv_planifie' => 'orange',
            'en_negociation' => 'purple',
            'qualifiee' => 'primary',
            'converti' => 'success',
            'perdu' => 'danger',
            default => 'gray',
        };
    }

    public function getPotentielLabelAttribute(): string
    {
        return self::POTENTIELS[$this->potentiel] ?? $this->potentiel;
    }

    public function getPotentielColorAttribute(): string
    {
        return match($this->potentiel) {
            'faible' => 'gray',
            'moyen' => 'info',
            'eleve' => 'warning',
            'tres_eleve' => 'success',
            default => 'gray',
        };
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source_detection] ?? $this->source_detection;
    }

    public function getAgeJoursAttribute(): int
    {
        return $this->date_detection
            ? $this->date_detection->diffInDays(now())
            : $this->created_at->diffInDays(now());
    }

    public function getEstNouvelleAttribute(): bool
    {
        return $this->age_jours <= 7;
    }

    public function getEstAncienneAttribute(): bool
    {
        return $this->age_jours > 30;
    }

    public function getEstConvertibleAttribute(): bool
    {
        return in_array($this->statut, ['qualifiee', 'en_negociation', 'rdv_planifie']);
    }

    public function getInterlocuteurCompletAttribute(): string
    {
        if (!$this->interlocuteur_nom) return 'Non défini';

        return trim(
            $this->interlocuteur_nom .
            ($this->interlocuteur_fonction ? ' - ' . $this->interlocuteur_fonction : '')
        );
    }

    public function getValeurEstimeeAttribute(): float
    {
        return match($this->potentiel) {
            'faible' => $this->chiffre_affaires * 0.01 ?? 1000,
            'moyen' => $this->chiffre_affaires * 0.02 ?? 5000,
            'eleve' => $this->chiffre_affaires * 0.05 ?? 10000,
            'tres_eleve' => $this->chiffre_affaires * 0.10 ?? 50000,
            default => 0,
        };
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function contacter(): void
    {
        $this->update([
            'statut' => 'contacte',
            'date_premier_contact' => $this->date_premier_contact ?? now(),
        ]);
    }

    public function planifierRDV(): void
    {
        $this->update(['statut' => 'rdv_planifie']);
    }

    public function demarrerNegociation(): void
    {
        $this->update(['statut' => 'en_negociation']);
    }

    public function marquerQualifiee(): void
    {
        $this->update(['statut' => 'qualifiee']);
    }

    public function convertirEnProspect(): ?Prospect
    {
        $prospect = Prospect::create([
            'nom' => $this->nom_entite,
            'type_pressenti' => $this->type_pressenti,
            'departement' => $this->departement,
            'telephone' => $this->telephone,
            'email' => $this->email,
            'adresse' => $this->adresse,
            'siret' => $this->siret,
            'secteur_activite' => $this->secteur_activite,
            'nb_salaries' => $this->nb_salaries,
            'chiffre_affaires' => $this->chiffre_affaires,
            'interlocuteur_nom' => $this->interlocuteur_nom,
            'interlocuteur_fonction' => $this->interlocuteur_fonction,
            'interlocuteur_telephone' => $this->interlocuteur_telephone,
            'interlocuteur_email' => $this->interlocuteur_email,
            'description' => "Converti depuis opportunité #{$this->id}\n" . $this->notes,
        ]);

        $this->update([
            'statut' => 'converti',
            'converti_en_prospect_id' => $prospect->id,
        ]);

        return $prospect;
    }

    public function marquerPerdue(string $raison): void
    {
        $this->update([
            'statut' => 'perdu',
            'raison_perte' => $raison,
        ]);
    }

    public function assigner(int $userId): void
    {
        $this->update(['assigne_a' => $userId]);
    }

    public function qualifier(string $statut): void
    {
        if (!array_key_exists($statut, self::STATUTS)) {
            throw new \InvalidArgumentException("Statut invalide : {$statut}");
        }

        $this->update(['statut' => $statut]);
    }

    public function ajouterNote(string $note): void
    {
        $this->update([
            'notes' => $this->notes
                ? $this->notes . "\n[" . now()->format('d/m/Y H:i') . "] {$note}"
                : "[". now()->format('d/m/Y H:i') . "] {$note}",
        ]);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeActives($query): Builder
    {
        return $query->whereNotIn('statut', ['converti', 'perdu']);
    }

    public function scopeConverties($query): Builder
    {
        return $query->where('statut', 'converti');
    }

    public function scopePerdues($query): Builder
    {
        return $query->where('statut', 'perdu');
    }

    public function scopeNouvelles($query): Builder
    {
        return $query->where('statut', 'nouveau');
    }

    public function scopeEnNegociation($query): Builder
    {
        return $query->where('statut', 'en_negociation');
    }

    public function scopePotentielEleve($query): Builder
    {
        return $query->whereIn('potentiel', ['eleve', 'tres_eleve']);
    }

    public function scopeParStatut($query, string $statut): Builder
    {
        return $query->where('statut', $statut);
    }

    public function scopeParSource($query, string $source): Builder
    {
        return $query->where('source_detection', $source);
    }

    public function scopeAssigneesA($query, int $userId): Builder
    {
        return $query->where('assigne_a', $userId);
    }

    public function scopeNonAssignees($query): Builder
    {
        return $query->whereNull('assigne_a');
    }

    public function scopeAnciennes($query, int $jours = 30): Builder
    {
        return $query->where('date_detection', '<', now()->subDays($jours))
                     ->whereIn('statut', ['nouveau', 'en_qualification']);
    }

    public function scopeDuMois($query): Builder
    {
        return $query->whereMonth('date_detection', now()->month)
                     ->whereYear('date_detection', now()->year);
    }

    public function scopeSansContact($query): Builder
    {
        return $query->whereNull('date_premier_contact')
                     ->where('statut', '!=', 'converti');
    }

    public function scopeARelancer($query): Builder
    {
        return $query->whereIn('statut', ['contacte', 'en_qualification'])
                     ->where('updated_at', '<', now()->subDays(7));
    }

    // ── Méthodes statiques KPIs ─────────────────────────────────────
    public static function getKpis(): array
    {
        return [
            'total' => static::count(),
            'actives' => static::actives()->count(),
            'converties' => static::converties()->count(),
            'perdues' => static::perdues()->count(),
            'taux_conversion' => static::getTauxConversion(),
            'taux_perte' => static::getTauxPerte(),
            'valeur_pipeline' => static::getValeurPipeline(),
            'par_statut' => static::getRepartitionParStatut(),
            'par_source' => static::getRepartitionParSource(),
            'par_potentiel' => static::getRepartitionParPotentiel(),
        ];
    }

    public static function getTauxConversion(): float
    {
        $total = static::count();
        if ($total === 0) return 0;

        $converties = static::where('statut', 'converti')->count();
        return round(($converties / $total) * 100, 1);
    }

    public static function getTauxPerte(): float
    {
        $total = static::count();
        if ($total === 0) return 0;

        $perdues = static::where('statut', 'perdu')->count();
        return round(($perdues / $total) * 100, 1);
    }

    public static function getValeurPipeline(): float
    {
        return static::whereIn('statut', [
            'en_qualification', 'contacte', 'rdv_planifie', 'en_negociation', 'qualifiee'
        ])->get()->sum(function ($opp) {
            return $opp->valeur_estimee;
        });
    }

    public static function getRepartitionParStatut(): array
    {
        return collect(self::STATUTS)
            ->mapWithKeys(function ($label, $statut) {
                return [$statut => static::where('statut', $statut)->count()];
            })
            ->toArray();
    }

    public static function getRepartitionParSource(): array
    {
        return static::selectRaw('source_detection, COUNT(*) as total')
            ->whereNotNull('source_detection')
            ->groupBy('source_detection')
            ->orderByDesc('total')
            ->get()
            ->pluck('total', 'source_detection')
            ->toArray();
    }

    public static function getRepartitionParPotentiel(): array
    {
        return collect(self::POTENTIELS)
            ->mapWithKeys(function ($label, $potentiel) {
                return [$potentiel => static::where('potentiel', $potentiel)->count()];
            })
            ->toArray();
    }

    // ── Boot ────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (Opportunite $opportunite) {
            if (!$opportunite->date_detection) {
                $opportunite->date_detection = now();
            }
            if (!$opportunite->statut) {
                $opportunite->statut = 'nouveau';
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────
    public function convertiEnProspect()
    {
        return $this->belongsTo(Prospect::class, 'converti_en_prospect_id');
    }

    public function assigneA()
    {
        return $this->belongsTo(User::class, 'assigne_a');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function appels()
    {
        return $this->morphMany(Appel::class, 'appelable');
    }

    public function rendezVous()
    {
        return $this->morphMany(RendezVous::class, 'rdvable');
    }
}
