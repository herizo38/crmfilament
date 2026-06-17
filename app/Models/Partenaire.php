<?php

namespace App\Models;

use App\Enums\OrganizationType;
use App\Enums\OrganizationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partenaire extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUTS = [
        'a_prospecter'          => 'À prospecter',
        'en_cours_prospection'  => 'En cours de prospection',
        'rdv_en_cours'          => 'RDV en cours',
        'signe_accord_cadre'    => 'Signé accord cadre',
        'convention_engagement' => 'Convention d\'engagement',
        'refus'                 => 'Refus',
        'inactif'               => 'Inactif',
    ];

    protected $fillable = [
        // ── Identité ─────────────────────────────────────────────────
        'nom',
        'entreprise',
        'nom_retenu',
        'siret',
        'type',
        'nomenclature_interne',
        'entreprise_mere_id',
        // ── Localisation ─────────────────────────────────────────────
        'adresse',
        'code_postal',
        'ville',
        'departement',
        'telephone',
        'email',
        // ── Activité ─────────────────────────────────────────────────
        'secteur_activite',
        'nb_salaries',
        'chiffre_affaires',
        // ── Statut / Suivi ────────────────────────────────────────────
        'statut',
        'date_modification_statut',
        'date_convention',
        'date_signature',       // ✅ Ajout MEA
        'annee_signature',
        // ── Fonctionnement partenariat ────────────────────────────────
        'possibilite_permanence',
        'replicable',
        // ✅ Ajout MEA : flag parrainage entreprise (OUI/NON)
        'parrainage_entreprise',
        // ✅ Ajout MEA : syndicat majoritaire au sein du CSE
        'syndicat_majoritaire',
        // ── Origine / Parrainage ──────────────────────────────────────
        'origine_contact',
        'parrain_marraine',
        'parrain_marraine_texte', // ✅ Ajout MEA : texte libre du parrain/marraine
        // ── Clés étrangères ───────────────────────────────────────────
        'entite_id',             // ✅ Ajout MEA : FK vers ENTITE_COMMERCIALE
        'commercial_id',
        'conseiller_id',
        'parrain_partenaire_id',
        'prospect_id',
        // ── Champs CSE (inchangés) ────────────────────────────────────
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
        // ── Syndicat (inchangés) ──────────────────────────────────────
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
        // ── Dirigeant (inchangés) ─────────────────────────────────────
        'dirigeant_nom',
        'dirigeant_prenom',
        'dirigeant_fonction',
        'dirigeant_telephone',
        'dirigeant_email',
        // ── Misc ──────────────────────────────────────────────────────
        'nombre_ventes_liees',
        'notes',
        'commentaire_import',
        'date_evaluation',
        'statut_prospection',
        'commentaires',          // ✅ Ajout MEA : champ commentaires du MEA
    ];

    protected $casts = [
        'type'                   => OrganizationType::class,
        'statut'                 => OrganizationStatus::class,
        'date_convention'        => 'date',
        'date_signature'         => 'date',
        'cse_date_fin_mandat'    => 'date',
        'date_modification_statut' => 'datetime',
        'date_evaluation'        => 'date',
        'cse_existence_juridique' => 'boolean',
        'parrainage_entreprise'  => 'boolean', // ✅ OUI/NON → bool
        'nb_salaries'            => 'integer',
        'chiffre_affaires'       => 'decimal:2',
        'nombre_ventes_liees'    => 'integer',
        'cse_nb_elus'            => 'integer',
        'annee_signature'        => 'integer',
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

    public function getTypeLabelAttribute(): string
    {
        return $this->type->value;
    }

    public function getNomCompletAttribute(): string
    {
        return $this->nom . ' (' . $this->type->value . ')';
    }

    /**
     * Nomenclature imposée par le CDC : « [Type] [Entreprise] [Ville] ».
     */
    public static function genererNomenclature($type, ?string $entreprise, ?string $ville): string
    {
        $typeLabel = $type instanceof OrganizationType
            ? $type->value
            : (OrganizationType::tryFrom((string) $type)?->value ?? (string) $type);

        return collect([$typeLabel, $entreprise, $ville])
            ->filter(fn($part) => filled($part))
            ->map(fn($part) => trim((string) $part))
            ->implode(' ');
    }

    public function getNomenclatureSuggereeAttribute(): string
    {
        return self::genererNomenclature($this->type, $this->entreprise, $this->ville);
    }

    public function getAdresseCompleteAttribute(): string
    {
        return trim($this->adresse . ', ' . $this->code_postal . ' ' . $this->ville);
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeByType($query, OrganizationType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatut($query, OrganizationStatus $statut)
    {
        return $query->where('statut', $statut);
    }

    public function scopeActifs($query)
    {
        return $query->whereIn('statut', [
            OrganizationStatus::AProspecter,
            OrganizationStatus::EnCoursProspection,
            OrganizationStatus::RdvEnCours,
        ]);
    }

    public function scopeConventionnes($query)
    {
        return $query->whereIn('statut', [
            OrganizationStatus::SigneAccordCadre,
            OrganizationStatus::ConventionEngagement,
        ]);
    }

    public function scopeARelancer($query, int $joursSansContact = 30)
    {
        return $query->whereIn('statut', [
            OrganizationStatus::AProspecter,
            OrganizationStatus::EnCoursProspection,
        ])->where(function ($q) use ($joursSansContact) {
            $q->whereNull('date_modification_statut')
              ->orWhere('date_modification_statut', '<=', now()->subDays($joursSansContact));
        });
    }

    public function scopeByEntite($query, int $entiteId)
    {
        return $query->where('entite_id', $entiteId);
    }

    // ── Méthodes métier ─────────────────────────────────────────────

    public function estActif(): bool
    {
        return !in_array($this->statut, [
            OrganizationStatus::Refus,
            OrganizationStatus::ConventionEngagement,
        ]);
    }

    public function estCSE(): bool
    {
        return $this->type === OrganizationType::CSE;
    }

    public function estSyndicat(): bool
    {
        return $this->type === OrganizationType::Syndicat;
    }

    public function estEntrepriseDirecte(): bool
    {
        return $this->type === OrganizationType::EntrepriseDirecte;
    }

    public function estAssociation(): bool
    {
        return $this->type === OrganizationType::Association;
    }

    public function changerStatut(OrganizationStatus $nouveauStatut): void
    {
        $this->update([
            'statut'                   => $nouveauStatut,
            'date_modification_statut' => now(),
        ]);
    }

    public function signerAccordCadre(): void
    {
        $this->changerStatut(OrganizationStatus::SigneAccordCadre);
    }

    public function signerConvention(): void
    {
        $this->update([
            'statut'                   => OrganizationStatus::ConventionEngagement,
            'date_convention'          => now(),
            'date_modification_statut' => now(),
        ]);
    }

    public function refuser(string $motif = null): void
    {
        $this->update([
            'statut'                   => OrganizationStatus::Refus,
            'date_modification_statut' => now(),
            'notes' => $motif ? $this->notes . "\nRefus: " . $motif : $this->notes,
        ]);
    }

    // ── Boot ────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::updating(function (Partenaire $partenaire) {
            if ($partenaire->isDirty('statut')) {
                $partenaire->date_modification_statut = now();

                if ($partenaire->statut === OrganizationStatus::ConventionEngagement) {
                    $partenaire->date_convention = now();
                }
            }
        });

        static::creating(function (Partenaire $partenaire) {
            if (!$partenaire->statut) {
                $partenaire->statut = OrganizationStatus::AProspecter;
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────

    public function entite()
    {
        return $this->belongsTo(EntiteCommerciale::class, 'entite_id');
    }

    public function entrepriseMere()
    {
        return $this->belongsTo(Partenaire::class, 'entreprise_mere_id');
    }

    public function filiales()
    {
        return $this->hasMany(Partenaire::class, 'entreprise_mere_id');
    }

    public function commercial()
    {
        return $this->belongsTo(User::class, 'commercial_id');
    }

    public function conseiller()
    {
        return $this->belongsTo(Consultant::class, 'conseiller_id');
    }

    public function parrainPartenaire()
    {
        return $this->belongsTo(Partenaire::class, 'parrain_partenaire_id');
    }

    public function filleuls()
    {
        return $this->hasMany(Partenaire::class, 'parrain_partenaire_id');
    }

    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }

    public function contacts()
    {
        return $this->hasMany(ContactPartenaire::class);
    }

    public function adresseCse()
    {
        return $this->hasOne(AdresseCse::class);
    }

    public function tarification()
    {
        return $this->hasOne(Tarification::class);
    }

    // ✅ Relations MEA ajoutées ────────────────────────────────────────

    public function historiqueConseillers()
    {
        return $this->hasMany(HistoriqueConseiller::class);
    }

    public function autresInterlocuteurs()
    {
        return $this->hasMany(AutresInterlocuteurs::class);
    }

    public function activiteVente()
    {
        return $this->hasOne(ActiviteVente::class);
    }

    public function activitePermanence()
    {
        return $this->hasOne(ActivitePermanence::class);
    }

    public function remboursementEmployeur()
    {
        return $this->hasOne(RemboursementEmployeur::class);
    }

    // ✅ Relations existantes conservées ──────────────────────────────

    public function appels()
    {
        return $this->morphMany(Appel::class, 'appelable');
    }

    public function rendezVous()
    {
        return $this->morphMany(RendezVous::class, 'rdvable');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function personnes()
    {
        return $this->hasMany(Client::class, 'partenaire_id');
    }
}
