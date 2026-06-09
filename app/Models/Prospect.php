<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use App\Enums\ProspectStatut;
use App\Enums\OrganizationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Prospect extends Model
{
    use SoftDeletes;

    protected $table = 'prospects';

    protected $casts = [
        'statut' => ProspectStatut::class,
        'date_premier_contact' => 'date',
        'rappel_planifie_at' => 'datetime',
        'qf_valide' => 'boolean',
        'qf_valide_at' => 'datetime',
        'nb_salaries' => 'integer',
        'chiffre_affaires' => 'decimal:2',
    ];

    protected $fillable = [
        'nom',
        'type_pressenti',
        'departement',
        'telephone',
        'telephone_alt',
        'email',
        'adresse',
        'code_postal',
        'ville',
        'siret',
        'secteur_activite',
        'nb_salaries',
        'chiffre_affaires',
        'statut',
        'teleprospecteur_id',
        'commercial_id',
        'date_premier_contact',
        'rappel_planifie_at',
        'interlocuteur_nom',
        'interlocuteur_fonction',
        'interlocuteur_telephone',
        'interlocuteur_email',
        'description',
        'motif_ko',
        'qf_valide',
        'valide_par',
        'qf_valide_at',
        // Infos CSE
        'cse_secretaire_nom',
        'cse_secretaire_prenom',
        'cse_secretaire_tel_direct',
        'cse_secretaire_tel_perso',
        'cse_secretaire_email_pro',
        'cse_secretaire_email_perso',
        'cse_tresorier_nom',
        'cse_tresorier_prenom',
        'cse_tresorier_tel_direct',
        'cse_tresorier_tel_perso',
        'cse_tresorier_email_pro',
        'cse_tresorier_email_perso',
        'cse_nb_elus',
        'cse_date_fin_mandat',
        'cse_existence_juridique',
        'cse_notes',
        // Infos Syndicat
        'syndicat_appartenance',
        'syndicat_nom_organisation',
        'syndicat_responsable_nom',
        'syndicat_responsable_prenom',
        'syndicat_responsable_fonction',
        'syndicat_tel_direct',
        'syndicat_tel_perso',
        'syndicat_email_pro',
        'syndicat_email_perso',
        'syndicat_perimetre',
        'syndicat_notes',
        // ── Dirigeant (commun à tous les types) ─────────────────────────
        'dirigeant_nom',
        'dirigeant_prenom',
        'dirigeant_fonction',
        'dirigeant_telephone',
        'dirigeant_email',

        'ordre_priorite'
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

    public function getStatutDescriptionAttribute(): string
    {
        return match ($this->statut) {
            ProspectStatut::AC => 'À contacter - Nouveau prospect',
            ProspectStatut::STD_NR => 'Standard non référencé - En attente',
            ProspectStatut::STD_Joint => 'Standard joint - En cours de qualification',
            ProspectStatut::CSE_NR => 'CSE non référencé - À qualifier',
            ProspectStatut::RP => 'Réponse positive - À suivre',
            ProspectStatut::RPC => 'Réponse positive CSE - Prioritaire',
            ProspectStatut::KO => 'KO - Prospect non intéressé',
            ProspectStatut::QF => 'Qualifié - Prêt pour commercial',
        };
    }

    public function getTypePressentiLabelAttribute(): string
    {
        if (!$this->type_pressenti) return 'Non défini';

        $types = OrganizationType::pourSelect();
        return $types[$this->type_pressenti] ?? $this->type_pressenti;
    }

    public function getEstQualifieAttribute(): bool
    {
        return $this->statut === ProspectStatut::QF;
    }

    public function getEstAPlanifierAttribute(): bool
    {
        return in_array($this->statut, [
            ProspectStatut::RP,
            ProspectStatut::RPC,
        ]);
    }

    public function getEstARelancerAttribute(): bool
    {
        return in_array($this->statut, [
            ProspectStatut::AC,
            ProspectStatut::STD_NR,
            ProspectStatut::CSE_NR,
        ]);
    }

    public function getEstKOAttribute(): bool
    {
        return $this->statut === ProspectStatut::KO;
    }

    public function getAdresseCompleteAttribute(): string
    {
        return collect([
            $this->adresse,
            $this->code_postal,
            $this->ville,
        ])->filter()->implode(', ');
    }

    public function getLocalisationAttribute(): string
    {
        return collect([
            $this->ville,
            $this->departement,
        ])->filter()->implode(' (') . ($this->departement ? ')' : '');
    }

    public function getInterlocuteurCompletAttribute(): string
    {
        if (!$this->interlocuteur_nom) return 'Non défini';

        return trim(
            $this->interlocuteur_nom .
                ($this->interlocuteur_fonction ? ' - ' . $this->interlocuteur_fonction : '')
        );
    }

    public function getDernierContactAttribute(): ?string
    {
        return $this->date_premier_contact?->diffForHumans();
    }

    public function getJoursDepuisPremierContactAttribute(): ?int
    {
        return $this->date_premier_contact?->diffInDays(now());
    }

    public function getJoursAvantRappelAttribute(): ?int
    {
        if (!$this->rappel_planifie_at) return null;

        if ($this->rappel_planifie_at->isPast()) {
            return -$this->rappel_planifie_at->diffInDays(now());
        }

        return $this->rappel_planifie_at->diffInDays(now());
    }

    public function getRappelEstEnRetardAttribute(): bool
    {
        return $this->rappel_planifie_at && $this->rappel_planifie_at->isPast()
            && !in_array($this->statut, [ProspectStatut::KO, ProspectStatut::QF]);
    }

    public function getTauxEngagementAttribute(): string
    {
        if ($this->statut === ProspectStatut::QF) return '⭐⭐⭐⭐⭐';
        if (in_array($this->statut, [ProspectStatut::RP, ProspectStatut::RPC])) return '⭐⭐⭐⭐';
        if ($this->statut === ProspectStatut::STD_Joint) return '⭐⭐⭐';
        if (in_array($this->statut, [ProspectStatut::STD_NR, ProspectStatut::CSE_NR])) return '⭐⭐';
        return '⭐';
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function marquerContact(): void
    {
        $this->update([
            'date_premier_contact' => $this->date_premier_contact ?? now(),
        ]);
    }

    public function changerStatut(ProspectStatut $nouveauStatut, ?string $notes = null): void
    {
        if ($nouveauStatut === ProspectStatut::KO && !$notes) {
            throw new \Exception("Un motif est obligatoire pour passer en KO");
        }

        $data = ['statut' => $nouveauStatut];

        if ($notes) {
            $data['description'] = $this->description
                ? $this->description . "\n[" . now()->format('d/m/Y H:i') . "] {$notes}"
                : "[" . now()->format('d/m/Y H:i') . "] {$notes}";
        }

        if ($nouveauStatut === ProspectStatut::KO) {
            $data['motif_ko'] = $notes;
        }

        $this->update($data);
    }

    public function qualifier(): void
    {
        $this->changerStatut(ProspectStatut::QF, 'Prospect qualifié');
    }

    public function marquerKO(string $motif): void
    {
        $this->changerStatut(ProspectStatut::KO, $motif);
    }

    public function marquerReponsePositive(): void
    {
        $this->changerStatut(ProspectStatut::RP, 'Réponse positive reçue');
    }

    public function marquerReponsePositiveCSE(): void
    {
        $this->changerStatut(ProspectStatut::RPC, 'Réponse positive CSE');
    }

    public function standardJoint(): void
    {
        $this->changerStatut(ProspectStatut::STD_Joint, 'Standard joint');
    }

    public function programmerRappel(\DateTime $date): void
    {
        $this->update([
            'rappel_planifie_at' => $date,
            'statut' => in_array($this->statut, [ProspectStatut::AC, ProspectStatut::KO])
                ? ProspectStatut::AC
                : $this->statut,
        ]);
    }

    public function annulerRappel(): void
    {
        $this->update(['rappel_planifie_at' => null]);
    }

    public function validerQF(int $userId): void
    {
        $this->update([
            'qf_valide' => true,
            'valide_par' => $userId,
            'qf_valide_at' => now(),
        ]);
    }

    public function assignerTeleprospecteur(int $userId): void
    {
        $this->update(['teleprospecteur_id' => $userId]);
    }

    public function assignerCommercial(int $userId): void
    {
        $this->update(['commercial_id' => $userId]);
    }

    public function ajouterNote(string $note): void
    {
        $this->update([
            'description' => $this->description
                ? $this->description . "\n[" . now()->format('d/m/Y H:i') . "] {$note}"
                : "[" . now()->format('d/m/Y H:i') . "] {$note}",
        ]);
    }

    public function mettreAJourContact(
        string $nom,
        string $fonction = null,
        string $telephone = null,
        string $email = null
    ): void {
        $this->update([
            'interlocuteur_nom' => $nom,
            'interlocuteur_fonction' => $fonction,
            'interlocuteur_telephone' => $telephone,
            'interlocuteur_email' => $email,
        ]);
    }



    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeActifs($query): Builder
    {
        return $query->whereNotIn('statut', [
            ProspectStatut::KO,
            ProspectStatut::QF,
        ]);
    }

    public function scopeQualifies($query): Builder
    {
        return $query->where('statut', ProspectStatut::QF);
    }

    public function scopeKO($query): Builder
    {
        return $query->where('statut', ProspectStatut::KO);
    }

    public function scopeARelancer($query): Builder
    {
        return $query->whereIn('statut', [
            ProspectStatut::AC->value,
            ProspectStatut::STD_NR->value,
            ProspectStatut::CSE_NR->value,
        ]);
    }

    public function scopeSansContact($query): Builder
    {
        return $query->whereNull('date_premier_contact');
    }

    public function scopeRappelPlanifie($query): Builder
    {
        return $query->whereNotNull('rappel_planifie_at')
            ->where('rappel_planifie_at', '>', now());
    }

    public function scopeRappelEnRetard($query): Builder
    {
        return $query->whereNotNull('rappel_planifie_at')
            ->where('rappel_planifie_at', '<', now())
            ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value]);
    }

    public function scopeAReponsePositive($query): Builder
    {
        return $query->whereIn('statut', [
            ProspectStatut::RP,
            ProspectStatut::RPC,
        ]);
    }

    public function scopeQFValides($query): Builder
    {
        return $query->where('qf_valide', true);
    }

    public function scopeQFNonValides($query): Builder
    {
        return $query->where('statut', ProspectStatut::QF)
            ->where('qf_valide', false);
    }

    public function scopeParTeleprospecteur($query, int $userId): Builder
    {
        return $query->where('teleprospecteur_id', $userId);
    }

    public function scopeParCommercial($query, int $userId): Builder
    {
        return $query->where('commercial_id', $userId);
    }

    public function scopeParDepartement($query, string $departement): Builder
    {
        return $query->where('departement', $departement);
    }

    public function scopeParTypePressenti($query, string $type): Builder
    {
        return $query->where('type_pressenti', $type);
    }

    public function scopeParStatut($query, ProspectStatut $statut): Builder
    {
        return $query->where('statut', $statut);
    }

    public function scopeDuMois($query): Builder
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    public function scopeSansActiviteDepuis($query, int $jours): Builder
    {
        return $query->where(function ($q) use ($jours) {
            $q->whereNull('date_premier_contact')
                ->orWhere('date_premier_contact', '<', now()->subDays($jours));
        })->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value]);
    }

    // ── Méthodes statiques KPIs ─────────────────────────────────────
    public static function getKpis(?int $teleprospecteurId = null): array
    {
        $query = static::query();

        if ($teleprospecteurId) {
            $query->where('teleprospecteur_id', $teleprospecteurId);
        }

        return [
            'total' => $query->count(),
            'actifs' => (clone $query)->actifs()->count(),
            'a_relancer' => (clone $query)->aRelancer()->count(),
            'rappels_en_retard' => (clone $query)->rappelEnRetard()->count(),
            'qualifies' => (clone $query)->qualifies()->count(),
            'ko' => (clone $query)->ko()->count(),
            'taux_qualification' => static::getTauxQualification($teleprospecteurId),
            'taux_ko' => static::getTauxKO($teleprospecteurId),
            'par_statut' => static::getRepartitionParStatut($teleprospecteurId),
            'par_type' => static::getRepartitionParType($teleprospecteurId),
            'nouveaux_mois' => (clone $query)->duMois()->count(),
        ];
    }

    public static function getTauxQualification(?int $teleprospecteurId = null): float
    {
        $query = static::query();

        if ($teleprospecteurId) {
            $query->where('teleprospecteur_id', $teleprospecteurId);
        }

        $total = $query->count();
        if ($total === 0) return 0;

        $qualifies = (clone $query)->qualifies()->count();
        return round(($qualifies / $total) * 100, 1);
    }

    public static function getTauxKO(?int $teleprospecteurId = null): float
    {
        $query = static::query();

        if ($teleprospecteurId) {
            $query->where('teleprospecteur_id', $teleprospecteurId);
        }

        $total = $query->count();
        if ($total === 0) return 0;

        $ko = (clone $query)->ko()->count();
        return round(($ko / $total) * 100, 1);
    }

    public static function getRepartitionParStatut(?int $teleprospecteurId = null): array
    {
        $query = static::query();

        if ($teleprospecteurId) {
            $query->where('teleprospecteur_id', $teleprospecteurId);
        }

        return collect(ProspectStatut::cases())
            ->mapWithKeys(function ($statut) use ($query) {
                return [$statut->value => (clone $query)->where('statut', $statut)->count()];
            })
            ->toArray();
    }

    public static function getRepartitionParType(?int $teleprospecteurId = null): array
    {
        $query = static::query();

        if ($teleprospecteurId) {
            $query->where('teleprospecteur_id', $teleprospecteurId);
        }

        return (clone $query)
            ->selectRaw('type_pressenti, COUNT(*) as total')
            ->whereNotNull('type_pressenti')
            ->groupBy('type_pressenti')
            ->orderByDesc('total')
            ->get()
            ->pluck('total', 'type_pressenti')
            ->toArray();
    }

    // ── Boot ────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (Prospect $prospect) {
            if (!$prospect->statut) {
                $prospect->statut = ProspectStatut::AC;
            }
        });

        static::updating(function (Prospect $prospect) {
            // Si le statut change vers STD_Joint, enregistrer le premier contact
            if (
                $prospect->isDirty('statut') &&
                $prospect->statut === ProspectStatut::STD_Joint &&
                !$prospect->date_premier_contact
            ) {
                $prospect->date_premier_contact = now();
            }
        });
    }

    public function convertirEnPartenaire(): ?Partenaire
    {
        if (!$this->estQualifie) {
            throw new \Exception("Seuls les prospects qualifiés (QF) peuvent être convertis");
        }

        DB::beginTransaction();
        try {
            $partenaire = Partenaire::create([
                // Infos de base
                'nom'                => $this->nom,
                'type'               => $this->type_pressenti ?? OrganizationType::EntrepriseDirecte->value,
                'siret'              => $this->siret,
                'telephone'          => $this->telephone,
                'email'              => $this->email,
                'adresse'            => $this->adresse,
                'code_postal'        => $this->code_postal,
                'ville'              => $this->ville,
                'departement'        => $this->departement,
                'secteur_activite'   => $this->secteur_activite,
                'nb_salaries'        => $this->nb_salaries,
                'chiffre_affaires'   => $this->chiffre_affaires,
                'commercial_id'      => $this->commercial_id,
                'statut'             => OrganizationStatus::AProspecter,
                'notes'              => "Converti depuis prospect #{$this->id}\n{$this->description}",

                // Dirigeant
                'dirigeant_nom'       => $this->dirigeant_nom,
                'dirigeant_prenom'    => $this->dirigeant_prenom,
                'dirigeant_fonction'  => $this->dirigeant_fonction,
                'dirigeant_telephone' => $this->dirigeant_telephone,
                'dirigeant_email'     => $this->dirigeant_email,

                // CSE
                'cse_secretaire_nom'        => $this->cse_secretaire_nom,
                'cse_secretaire_prenom'     => $this->cse_secretaire_prenom,
                'cse_secretaire_tel_direct' => $this->cse_secretaire_tel_direct,
                'cse_secretaire_tel_perso'  => $this->cse_secretaire_tel_perso,
                'cse_secretaire_email_pro'  => $this->cse_secretaire_email_pro,
                'cse_secretaire_email_perso' => $this->cse_secretaire_email_perso,
                'cse_tresorier_nom'         => $this->cse_tresorier_nom,
                'cse_tresorier_prenom'      => $this->cse_tresorier_prenom,
                'cse_tresorier_tel_direct'  => $this->cse_tresorier_tel_direct,
                'cse_tresorier_tel_perso'   => $this->cse_tresorier_tel_perso,
                'cse_tresorier_email_pro'   => $this->cse_tresorier_email_pro,
                'cse_tresorier_email_perso' => $this->cse_tresorier_email_perso,
                'cse_nb_elus'               => $this->cse_nb_elus,
                'cse_date_fin_mandat'       => $this->cse_date_fin_mandat,
                'cse_existence_juridique'   => $this->cse_existence_juridique,
                'cse_notes'                 => $this->cse_notes,

                // Syndicat
                'syndicat_appartenance'          => $this->syndicat_appartenance,
                'syndicat_nom_organisation'      => $this->syndicat_nom_organisation,
                'syndicat_responsable_nom'       => $this->syndicat_responsable_nom,
                'syndicat_responsable_prenom'    => $this->syndicat_responsable_prenom,
                'syndicat_responsable_fonction'  => $this->syndicat_responsable_fonction,
                'syndicat_tel_direct'            => $this->syndicat_tel_direct,
                'syndicat_tel_perso'             => $this->syndicat_tel_perso,
                'syndicat_email_pro'             => $this->syndicat_email_pro,
                'syndicat_email_perso'           => $this->syndicat_email_perso,
                'syndicat_perimetre'             => $this->syndicat_perimetre,
                'syndicat_notes'                 => $this->syndicat_notes,
            ]);

            $this->update([
                'description' => $this->description . "\n[Conversion] Partenaire créé le " . now()->format('d/m/Y H:i'),
            ]);

            DB::commit();
            return $partenaire;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ── Relations ────────────────────────────────────────────────────
    public function teleprospecteur()
    {
        return $this->belongsTo(User::class, 'teleprospecteur_id');
    }

    public function commercial()
    {
        return $this->belongsTo(User::class, 'commercial_id');
    }

    public function validePar()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function rendezVous()
    {
        return $this->morphMany(RendezVous::class, 'rdvable');
    }

    public function appels()
    {
        return $this->morphMany(Appel::class, 'appelable');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function opportunite()
    {
        return $this->hasOne(Opportunite::class, 'converti_en_prospect_id');
    }
}
