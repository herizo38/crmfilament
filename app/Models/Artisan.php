<?php

namespace App\Models;

use App\Enums\CorpsDeMetier;
use App\Enums\StatutCompteArtisan;
use App\Enums\CanalAlerte;
use App\Enums\ModeAgendaArtisan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Artisan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nom',
        'prenom',
        'raison_sociale',
        'siret',
        'corps_de_metier',
        'zone_intervention',
        'telephone_principal',
        'telephone_secondaire',
        'email',
        'canal_alerte',
        'statut_compte',
        'formule_souscrite',     // NOUVEAU
        'mode_agenda',           // NOUVEAU
        'plages_disponibilite',  // NOUVEAU (JSON — requis si Mode A)
        'date_souscription',
        'date_activation',
        'agenda_disponibilites',
        'note_moyenne',
        'nb_interventions',
        'notes',
    ];

    protected $casts = [
        'corps_de_metier'        => CorpsDeMetier::class,
        'statut_compte'          => StatutCompteArtisan::class,
        'canal_alerte'           => 'array',              // MODIFIÉ : devient JSON array multi-valeurs
        'mode_agenda'            => ModeAgendaArtisan::class,  // NOUVEAU
        'plages_disponibilite'   => 'array',              // NOUVEAU
        'date_souscription'      => 'date',
        'date_activation'        => 'date',
        'agenda_disponibilites'  => 'boolean',
        'note_moyenne'           => 'decimal:2',
        'nb_interventions'       => 'integer',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getNomCompletAttribute(): string
    {
        return trim("{$this->prenom} {$this->nom}");
    }

    public function getRaisonSocialeCompleteAttribute(): string
    {
        if ($this->raison_sociale) {
            return $this->raison_sociale . ' (' . $this->nom_complet . ')';
        }
        return $this->nom_complet;
    }

    public function getPrioriteSegmentAttribute(): string
    {
        return $this->corps_de_metier->estPrioritaire() ? 'Haute' : 'Standard';
    }

    public function getStatutLabelAttribute(): string
    {
        return $this->statut_compte->label();
    }

    public function getStatutColorAttribute(): string
    {
        return $this->statut_compte->color();
    }

    public function getMetierLabelAttribute(): string
    {
        return $this->corps_de_metier->label();
    }

    public function getMetierColorAttribute(): string
    {
        return $this->corps_de_metier->color();
    }

    public function getMetierIconAttribute(): string
    {
        return $this->corps_de_metier->icon();
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeActifs($query)
    {
        return $query->where('statut_compte', StatutCompteArtisan::Actif->value);
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut_compte', StatutCompteArtisan::EnAttenteActivation->value);
    }

    public function scopeSuspendus($query)
    {
        return $query->where('statut_compte', StatutCompteArtisan::Suspendu->value);
    }

    public function scopeByMetier($query, CorpsDeMetier $metier)
    {
        return $query->where('corps_de_metier', $metier->value);
    }

    public function scopePrioritaires($query)
    {
        return $query->whereIn('corps_de_metier', array_map(
            fn($e) => $e->value,
            CorpsDeMetier::metiersPrioritaires()
        ));
    }

    public function scopeDisponibles($query)
    {
        return $query->where('agenda_disponibilites', true)
                     ->where('statut_compte', StatutCompteArtisan::Actif->value);
    }

    public function scopeBienNotes($query, float $minimum = 4.0)
    {
        return $query->where('note_moyenne', '>=', $minimum);
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function estActif(): bool
    {
        return $this->statut_compte === StatutCompteArtisan::Actif;
    }

    public function estEnAttente(): bool
    {
        return $this->statut_compte === StatutCompteArtisan::EnAttenteActivation;
    }

    public function estSuspendu(): bool
    {
        return $this->statut_compte === StatutCompteArtisan::Suspendu;
    }

    public function estDisponible(): bool
    {
        return $this->estActif() && $this->agenda_disponibilites;
    }

    // NOUVELLES MÉTHODES POUR LE MODE AGENDA
    public function estModeAStructure(): bool
    {
        return $this->mode_agenda === ModeAgendaArtisan::ModeA;
    }

    public function estModeBRappel(): bool
    {
        return $this->mode_agenda === ModeAgendaArtisan::ModeB;
    }

    // NOUVELLE MÉTHODE POUR LA FACTURATION
    public function peutEtreFacture(): bool
    {
        return !empty($this->siret) && strlen($this->siret) === 14;
    }

    public function activer(): void
    {
        $this->update([
            'statut_compte'   => StatutCompteArtisan::Actif,
            'date_activation' => now(),
        ]);
    }

    public function suspendre(string $motif = null): void
    {
        $this->update([
            'statut_compte' => StatutCompteArtisan::Suspendu,
            'notes'         => $motif ? $this->notes . "\nSuspension: " . $motif : $this->notes,
        ]);
    }

    public function reactiver(): void
    {
        $this->update([
            'statut_compte' => StatutCompteArtisan::Actif,
        ]);
    }

    public function getTauxSatisfactionAttribute(): float
    {
        $total = $this->rapportsSatisfaction()->count();
        if ($total === 0) return 0;

        $satisfaits = $this->rapportsSatisfaction()
            ->where('note_nps', '>=', 8)
            ->count();

        return round(($satisfaits / $total) * 100, 1);
    }

    public function getDerniereInterventionAttribute()
    {
        return $this->tickets()
            ->whereHas('ficheP2')
            ->latest()
            ->first();
    }

    public function getDelaiMoyenInterventionHeuresAttribute(): float
    {
        return $this->tickets()
            ->whereNotNull('rdv_planifie_at')
            ->whereNotNull('date_creation')
            ->get()
            ->avg(function ($ticket) {
                return $ticket->date_creation->diffInHours($ticket->rdv_planifie_at);
            }) ?? 0;
    }

    // ── Boot ────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (Artisan $artisan) {
            if (!$artisan->statut_compte) {
                $artisan->statut_compte = StatutCompteArtisan::EnAttenteActivation;
            }
            if (!$artisan->date_souscription) {
                $artisan->date_souscription = now();
            }
            if (!$artisan->canal_alerte) {
                $artisan->canal_alerte = [CanalAlerte::LesDeux->value]; // MODIFIÉ : array multi-valeurs
            }
            if (!$artisan->mode_agenda) {
                $artisan->mode_agenda = ModeAgendaArtisan::ModeA; // Valeur par défaut
            }
        });

        static::updating(function (Artisan $artisan) {
            if ($artisan->isDirty('statut_compte') &&
                $artisan->statut_compte === StatutCompteArtisan::Actif &&
                !$artisan->date_activation) {
                $artisan->date_activation = now();
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function ticketsEnCours()
    {
        return $this->tickets()->whereIn('statut', [
            'rdv_planifie',
            'artisan_confirme',
            'en_attente_confirmation_artisan',
        ]);
    }

    public function prospection()
    {
        return $this->hasOne(ArtisanProspection::class);
    }

    public function rapportsSatisfaction()
    {
        return $this->hasMany(RapportSatisfactionP6::class);
    }

    public function reclamations()
    {
        return $this->hasManyThrough(ReclamationP8::class, RapportSatisfactionP6::class);
    }

    public function rendezVous()
    {
        return $this->morphMany(RendezVous::class, 'rdvable');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    // NOUVELLES RELATIONS
    public function devis()
    {
        return $this->hasMany(Devis::class);
    }

    public function bonsDeCommande()
    {
        return $this->hasMany(BonDeCommande::class);
    }

    public function factures()
    {
        return $this->hasMany(Facture::class);
    }
}
