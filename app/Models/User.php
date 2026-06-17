<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use App\Services\Crm\CrmProfileService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'secteur',
        'actif',
        'role_cache',
        'google_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'google_token'      => 'array',
            'actif'             => 'boolean',
        ];
    }

    /**
     * Get the user's name for Filament display.
     */
    public function getFilamentName(): string
    {
        return trim($this->prenom . ' ' . $this->nom) ?: $this->email;
    }

    /**
     * Get the user's name attribute (compatibilité Filament).
     */
    public function getNameAttribute(): string
    {
        return $this->getFilamentName();
    }
    /**
     * Determine if the user can access the given Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return app(CrmProfileService::class)->userCanAccessPanel($this, $panel->getId());
    }


    public function hasRoleCache(string $role): bool
    {
        return $this->role_cache === $role;
    }

    public function hasAllRolesCache(array $roles): bool
    {
        return in_array($this->role_cache, $roles);
    }


    // ── Constantes de rôles ─────────────────────────────────────────
    const ROLE_SUPER_ADMIN       = 'super_admin';
    const ROLE_ADMIN             = 'administrateur';
    const ROLE_COMMERCIAL        = 'commercial';
    const ROLE_TELEPROSPECTEUR   = 'teleprospecteur';
    const ROLE_OPERATEUR         = 'operateur_n1';
    const ROLE_BACK_OFFICE       = 'back_office';
    const ROLE_SUPERVISEUR       = 'responsable_plateau';

    const ROLES = [
        self::ROLE_SUPER_ADMIN     => 'Super Administrateur',
        self::ROLE_ADMIN           => 'Administrateur',
        self::ROLE_COMMERCIAL      => 'Commercial',
        self::ROLE_TELEPROSPECTEUR => 'Téléprospecteur',
        self::ROLE_OPERATEUR       => 'Opérateur N1',
        self::ROLE_BACK_OFFICE     => 'Back-Office',
        self::ROLE_SUPERVISEUR     => 'Responsable Plateau',
    ];

    const SECTEURS = [
        'nord'     => 'Nord',
        'sud'      => 'Sud',
        'est'      => 'Est',
        'ouest'    => 'Ouest',
        'idf'      => 'Île-de-France',
        'national' => 'National',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    public function getInitialesAttribute(): string
    {
        return strtoupper(
            substr($this->prenom, 0, 1) .
                substr($this->nom, 0, 1)
        );
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role_cache] ?? $this->roles->first()?->name ?? 'Non défini';
    }

    public function getRoleColorAttribute(): string
    {
        return match ($this->role_cache) {
            self::ROLE_SUPER_ADMIN     => 'danger',
            self::ROLE_ADMIN           => 'warning',
            self::ROLE_COMMERCIAL      => 'success',
            self::ROLE_TELEPROSPECTEUR => 'info',
            self::ROLE_OPERATEUR       => 'primary',
            self::ROLE_BACK_OFFICE     => 'gray',
            self::ROLE_SUPERVISEUR     => 'warning',
            default                    => 'gray',
        };
    }

    public function getSecteurLabelAttribute(): string
    {
        return self::SECTEURS[$this->secteur] ?? $this->secteur ?? 'Non défini';
    }

    public function getStatutLabelAttribute(): string
    {
        return $this->actif ? 'Actif' : 'Inactif';
    }

    public function getGoogleConnecteAttribute(): bool
    {
        return !empty($this->google_token);
    }

    // ── Helpers rôles — délèguent à Spatie HasRoles ────────────────
    // Note : hasRole() est déjà fourni par Spatie\Permission\Traits\HasRoles
    // On surcharge uniquement pour supporter le tableau en plus de la string

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->pluck('name')->intersect($roles)->isNotEmpty();
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles->pluck('name')
            ->intersect(['super_admin', 'administrateur'])
            ->isNotEmpty();
    }
    public function isAdmin(): bool
    {
        return $this->hasRoleCache(self::ROLE_ADMIN) || $this->isSuperAdmin();
    }
    public function isCommercial(): bool
    {
        return $this->hasRoleCache(self::ROLE_COMMERCIAL);
    }
    public function isTeleprospecteur(): bool
    {
        return $this->hasRoleCache(self::ROLE_TELEPROSPECTEUR);
    }
    public function isOperateur(): bool
    {
        return $this->hasRoleCache(self::ROLE_OPERATEUR);
    }
    public function isBackOffice(): bool
    {
        return $this->hasRoleCache(self::ROLE_BACK_OFFICE);
    }
    public function isSuperviseur(): bool
    {
        return $this->hasRoleCache(self::ROLE_SUPERVISEUR);
    }

    // Assigner un rôle Spatie ET mettre à jour role_cache en même temps
    public function assignRoleWithCache(string $role): void
    {
        $this->syncRoles([$role]);
        $this->update(['role_cache' => $role]);
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function activer(): void
    {
        $this->update(['actif' => true]);
    }
    public function desactiver(): void
    {
        $this->update(['actif' => false]);
    }

    public function changerRole(string $role): void
    {
        if (!array_key_exists($role, self::ROLES)) {
            throw new \InvalidArgumentException("Rôle invalide : {$role}");
        }
        $this->assignRoleWithCache($role);
    }

    public function connecterGoogle(array $token): void
    {
        $this->update(['google_token' => $token]);
    }
    public function deconnecterGoogle(): void
    {
        $this->update(['google_token' => null]);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopeInactifs(Builder $query): Builder
    {
        return $query->where('actif', false);
    }

    public function scopeParRole(Builder $query, string $role): Builder
    {
        return $query->where('role_cache', $role);
    }

    public function scopeCommerciaux(Builder $query): Builder
    {
        return $query->where('role_cache', self::ROLE_COMMERCIAL);
    }

    public function scopeTeleprospecteurs(Builder $query): Builder
    {
        return $query->where('role_cache', self::ROLE_TELEPROSPECTEUR);
    }

    public function scopeOperateurs(Builder $query): Builder
    {
        return $query->where('role_cache', self::ROLE_OPERATEUR);
    }

    public function scopeGoogleConnectes(Builder $query): Builder
    {
        return $query->whereNotNull('google_token');
    }

    public function scopeRecherche(Builder $query, string $terme): Builder
    {
        return $query->where(function ($q) use ($terme) {
            $q->where('nom', 'like', "%{$terme}%")
                ->orWhere('prenom', 'like', "%{$terme}%")
                ->orWhere('email', 'like', "%{$terme}%");
        });
    }

    // ── Boot ────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (!isset($user->actif)) {
                $user->actif = true;
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────
    public function ticketsOperateur()
    {
        return $this->hasMany(Ticket::class, 'operateur_id');
    }

    public function prospectsTeleprospecteur()
    {
        return $this->hasMany(Prospect::class, 'teleprospecteur_id');
    }

    public function prospectsCommercial()
    {
        return $this->hasMany(Prospect::class, 'commercial_id');
    }

    public function partenaires()
    {
        return $this->hasMany(Partenaire::class, 'commercial_id');
    }

    // Alias utilisé dans certaines Resources
    public function partenairesAssignes()
    {
        return $this->hasMany(Partenaire::class, 'commercial_id');
    }

    public function rendezVousCommercial()
    {
        return $this->hasMany(RendezVous::class, 'commercial_id');
    }

    public function rendezVousTeleprospecteur()
    {
        return $this->hasMany(RendezVous::class, 'teleprospecteur_id');
    }

    public function appels()
    {
        return $this->hasMany(Appel::class, 'user_id');
    }

    public function artisanProspections()
    {
        return $this->hasMany(ArtisanProspection::class, 'teleprospecteur_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function reclamationsSupervisees()
    {
        return $this->hasMany(ReclamationP8::class, 'superviseur_id');
    }

    public function rapportsSatisfaction()
    {
        return $this->hasMany(RapportSatisfactionP6::class, 'operateur_id');
    }

    public function prospectsValides()
    {
        return $this->hasMany(Prospect::class, 'valide_par');
    }

    public function opportunites()
    {
        return $this->hasMany(Opportunite::class, 'assigne_a');
    }
}
