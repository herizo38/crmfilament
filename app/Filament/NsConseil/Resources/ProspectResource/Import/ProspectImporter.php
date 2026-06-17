<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\Import;

use App\Enums\OrganizationType;
use App\Enums\ProspectStatut;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Support\Str;

class ProspectImporter
{
    protected array $errors  = [];
    protected int   $created = 0;
    protected int   $updated = 0;
    protected int   $skipped = 0;

    protected array $columnMapping = [];
    protected array $defaults      = [];
    protected array $rowWarnings   = [];

    public static function getRequiredColumns(): array
    {
        return ['nom', 'telephone'];
    }

    // ── Point d'entrée ────────────────────────────────────────────────
    public function import(array $rows, string $sourceSheet = '', array $defaults = []): array
    {
        $this->defaults = $defaults;

        $headerRowIndex = $this->findHeaderRow($rows);

        if ($headerRowIndex === null) {
            $this->errors[] = "Impossible de trouver la ligne d'en-tête (colonne 'Nom' ou 'Raison sociale' attendue)";
            return $this->getResult();
        }

        $this->buildColumnMapping($rows[$headerRowIndex]);

        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            if ($this->isEmptyRow($row)) {
                continue;
            }

            try {
                $this->rowWarnings = [];
                $data = $this->mapRow($row);

                foreach ($this->rowWarnings as $warning) {
                    $this->errors[] = "Ligne " . ($i + 1) . " : {$warning}";
                }

                if (empty($data['nom'])) {
                    $this->skipped++;
                    $this->errors[] = "Ligne " . ($i + 1) . " : Nom manquant — ignorée";
                    continue;
                }

                $telephone = $data['telephone'] ?? null;
                if ($telephone) {
                    $prospect = Prospect::updateOrCreate(
                        ['telephone' => $telephone],
                        $data
                    );
                } else {
                    $prospect = Prospect::updateOrCreate(
                        ['nom' => $data['nom'], 'departement' => $data['departement'] ?? null],
                        $data
                    );
                }

                $prospect->wasRecentlyCreated ? $this->created++ : $this->updated++;

            } catch (\Throwable $e) {
                $this->errors[] = "Ligne " . ($i + 1) . " : " . $e->getMessage();
            }
        }

        return $this->getResult();
    }

    // ── Détection en-tête ─────────────────────────────────────────────
    protected function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            if (empty($row)) continue;
            foreach ($row as $cell) {
                $cell = mb_strtolower(trim((string) $cell));
                if (
                    str_contains($cell, 'nom') ||
                    str_contains($cell, 'raison sociale') ||
                    str_contains($cell, 'entreprise') ||
                    str_contains($cell, 'telephone') ||
                    str_contains($cell, 'téléphone')
                ) {
                    return $index;
                }
            }
        }
        return null;
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') return false;
        }
        return true;
    }

    // ── Normalisation pour la reconnaissance d'en-têtes ────────────────
    // Supprime accents, apostrophes, espaces, underscores… afin que
    // "Chiffred'affaires" et "Chiffre d'affaires" (ou "CA") soient
    // reconnus comme la même colonne, quel que soit le modèle de fichier.
    protected function normalizeForMatch(string $value): string
    {
        $value = mb_strtolower(trim($value));

        $value = strtr($value, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c', 'œ' => 'oe', '’' => "'",
        ]);

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    // ── Mapping colonnes ──────────────────────────────────────────────
    protected function buildColumnMapping(array $headerRow): void
    {
        $fieldAliases = [
            // ── Identification ──────────────────────────────────────
            'nom' => [
                'nom', 'raison sociale', 'raison_sociale', 'entreprise',
                'organisation', 'entite', 'entité',
            ],
            'numero_ordre' => [
                'n°', 'numero', 'numéro', 'numero_ordre', 'ordre',
            ],
            'type_pressenti' => [
                'type', 'type pressenti', 'type_pressenti', 'categorie', 'catégorie',
            ],
            'siret'           => ['siret', 'n° siret', 'numero siret'],
            'departement'     => ['dpt', 'departement', 'département', 'dep'],
            'code_postal'     => ['cp', 'code postal', 'code_postal'],
            'ville'           => ['ville', 'commune'],
            'adresse'         => ['adresse', 'address'],
            'secteur_activite' => [
                "secteur d'activité", "secteur d'activites", 'secteur_activite', 'secteur',
            ],
            'nb_salaries' => [
                'nbrs de salariés', 'nb salariés', 'nb_salaries', 'salariés', 'effectif',
                'nbr de salariés',
            ],
            'chiffre_affaires' => ['ca', "chiffre d'affaires", 'chiffre_affaires'],

            // ── Coordonnées ─────────────────────────────────────────
            'telephone' => [
                'téléphone', 'telephone', 'tel', 'tél', 'téléphone 1', 'telephone 1', 'tel 1',
            ],
            'telephone_alt'           => ['téléphone 2', 'telephone 2', 'tel 2', 'tél 2'],
            'email'                   => ['email', 'mail', 'e-mail'],

            // ── Interlocuteur ────────────────────────────────────────
            'interlocuteur_nom'       => ['interlocuteur', 'contact', 'interlocuteur nom', 'nom contact'],
            'interlocuteur_fonction'  => ['fonction', 'poste', 'titre'],
            'interlocuteur_telephone' => ['tel interlocuteur', 'tél interlocuteur'],
            'interlocuteur_email'     => ['email interlocuteur', 'mail interlocuteur'],

            // ── Pipeline ─────────────────────────────────────────────
            'statut'              => ['statut', 'etat', 'état', 'situation'],
            'teleprospecteur'     => ['conseiller', 'téléprospecteur', 'teleprospecteur', 'agent'],
            'commercial'          => ['commercial', 'commercial assigné', 'commercial_id'],
            'date_premier_contact' => [
                'date de 1er contact', 'date de premier contact', 'date premier contact',
                '1er contact', 'date de contact',
            ],
            'rappel_planifie_at'  => [
                'rappel', 'date rappel', 'rappel planifié', 'date de rappel',
            ],
            'description' => [
                'commentaire', 'commentaires', 'notes', 'description',
                'commentaires/situation actuelle', 'situation actuelle',
            ],

            // ── Dirigeant ────────────────────────────────────────────
            'dirigeant_nom'       => ['dirigeant nom', 'dirigeant_nom', 'nom dirigeant'],
            'dirigeant_prenom'    => ['dirigeant prenom', 'dirigeant_prenom', 'prénom dirigeant'],
            'dirigeant_fonction'  => ['dirigeant fonction', 'dirigeant_fonction', 'fonction dirigeant'],
            'dirigeant_telephone' => ['dirigeant tel', 'dirigeant_telephone', 'tel dirigeant'],
            'dirigeant_email'     => ['dirigeant email', 'dirigeant_email', 'email dirigeant'],

            // ── CSE ──────────────────────────────────────────────────
            'cse_secretaire_nom'         => ['secretaire nom', 'cse secretaire nom', 'nom secrétaire'],
            'cse_secretaire_prenom'      => ['secretaire prenom', 'cse secretaire prenom', 'prénom secrétaire'],
            'cse_secretaire_tel_direct'  => ['secretaire tel direct', 'cse secretaire tel', 'tel secrétaire'],
            'cse_secretaire_tel_perso'   => ['secretaire tel perso', 'cse secretaire tel perso'],
            'cse_secretaire_email_pro'   => ['secretaire email', 'cse secretaire email', 'email secrétaire'],
            'cse_secretaire_email_perso' => ['secretaire email perso', 'cse secretaire email perso'],
            'cse_tresorier_nom'          => ['tresorier nom', 'cse tresorier nom', 'nom trésorier'],
            'cse_tresorier_prenom'       => ['tresorier prenom', 'cse tresorier prenom', 'prénom trésorier'],
            'cse_tresorier_tel_direct'   => ['tresorier tel direct', 'cse tresorier tel', 'tel trésorier'],
            'cse_tresorier_tel_perso'    => ['tresorier tel perso', 'cse tresorier tel perso'],
            'cse_tresorier_email_pro'    => ['tresorier email', 'cse tresorier email', 'email trésorier'],
            'cse_tresorier_email_perso'  => ['tresorier email perso', 'cse tresorier email perso'],
            'cse_nb_elus'                => ['nb elus', 'cse nb elus', 'nombre élus'],
            'cse_date_fin_mandat'        => ['fin mandat', 'cse fin mandat', 'date fin mandat'],
            'cse_existence_juridique'    => ['existence juridique', 'cse existence juridique'],
            'cse_notes'                  => ['notes cse', 'cse notes', 'commentaires cse'],

            // ── Syndicat ─────────────────────────────────────────────
            'syndicat_appartenance'         => ['appartenance syndicale', 'syndicat appartenance', 'syndicat'],
            'syndicat_nom_organisation'     => ['syndicat nom', 'nom organisation syndicale'],
            'syndicat_responsable_nom'      => ['responsable syndicat nom', 'syndicat responsable nom'],
            'syndicat_responsable_prenom'   => ['responsable syndicat prenom', 'syndicat responsable prenom'],
            'syndicat_responsable_fonction' => ['responsable syndicat fonction', 'syndicat responsable fonction'],
            'syndicat_tel_direct'           => ['syndicat tel direct', 'tel syndicat'],
            'syndicat_tel_perso'            => ['syndicat tel perso'],
            'syndicat_email_pro'            => ['syndicat email', 'email syndicat'],
            'syndicat_email_perso'          => ['syndicat email perso'],
            'syndicat_perimetre'            => ['périmetre syndicat', 'syndicat perimetre', 'périmètre'],
            'syndicat_notes'                => ['notes syndicat', 'syndicat notes', 'commentaires syndicat'],
        ];

        // En-têtes normalisés une seule fois.
        $normalizedHeader = [];
        foreach ($headerRow as $colIndex => $colName) {
            $normalizedHeader[$colIndex] = $this->normalizeForMatch((string) $colName);
        }

        // Verrou anti-collision : une colonne déjà attribuée à un champ
        // ne peut plus être reprise par un autre champ (évite qu'un alias
        // trop générique ne « vole » une colonne destinée à un autre champ).
        $usedColumns = [];

        foreach ($fieldAliases as $field => $aliases) {
            $normalizedAliases = array_map(fn($a) => $this->normalizeForMatch($a), $aliases);

            foreach ($normalizedHeader as $colIndex => $colName) {
                if ($colName === '' || in_array($colIndex, $usedColumns, true)) {
                    continue;
                }

                foreach ($normalizedAliases as $alias) {
                    if ($alias === '') continue;

                    // Les alias très courts (≤2 caractères, ex: "ca", "cp")
                    // exigent une égalité stricte pour éviter les faux positifs ;
                    // les alias plus longs et distinctifs peuvent matcher en inclusion.
                    $isMatch = ($colName === $alias)
                        || (mb_strlen($alias) >= 3 && str_contains($colName, $alias));

                    if ($isMatch) {
                        $this->columnMapping[$field] = $colIndex;
                        $usedColumns[] = $colIndex;
                        break 2;
                    }
                }
            }
        }
    }

    // ── Mapping d'une ligne ────────────────────────────────────────────
    protected function mapRow(array $row): array
    {
        $get = function (string $field) use ($row): mixed {
            $index = $this->columnMapping[$field] ?? null;
            if ($index === null || !array_key_exists($index, $row)) return null;
            $value = $row[$index];
            return is_string($value) ? trim($value) : $value;
        };

        $cp          = $this->cleanCodePostal($get('code_postal'));
        $departement = $this->extractDepartement($get('departement'))
            ?? ($cp ? substr($cp, 0, 2) : null);

        $statut = $this->resolveStatut($get('statut'))
            ?? ($this->defaults['statut'] ?? ProspectStatut::AC->value);

        $conseillerBrut    = $get('teleprospecteur');
        $teleprospecteurId = $this->resolveOrCreateUserId($conseillerBrut, User::ROLE_COMMERCIAL);

        $commercialBrut = $get('commercial');
        $commercialId   = $this->resolveOrCreateUserId($commercialBrut, User::ROLE_COMMERCIAL);

        $data = [
            // ── Identification ──────────────────────────────────────
            'nom'              => $get('nom'),
            'numero_ordre'     => $this->cleanInt($get('numero_ordre')),
            'type_pressenti'   => $this->resolveType($get('type_pressenti'))
                                    ?? ($this->defaults['type_pressenti'] ?? null),
            'siret'            => $this->cleanSiret($get('siret')),
            'departement'      => $departement,
            'code_postal'      => $cp,
            'ville'            => $get('ville'),
            'adresse'          => $get('adresse'),
            'secteur_activite' => $get('secteur_activite') ?: ($this->defaults['secteur_activite'] ?? null),
            'nb_salaries'      => $this->cleanInt($get('nb_salaries')),
            'chiffre_affaires' => $this->cleanDecimal($get('chiffre_affaires')),

            // ── Coordonnées ─────────────────────────────────────────
            'telephone'     => $this->cleanTelephone($get('telephone')),
            'telephone_alt' => $this->cleanTelephone($get('telephone_alt')),
            'email'         => $get('email'),

            // ── Interlocuteur ────────────────────────────────────────
            'interlocuteur_nom'       => $get('interlocuteur_nom'),
            'interlocuteur_fonction'  => $get('interlocuteur_fonction'),
            'interlocuteur_telephone' => $this->cleanTelephone($get('interlocuteur_telephone')),
            'interlocuteur_email'     => $get('interlocuteur_email'),

            // ── Pipeline ─────────────────────────────────────────────
            'statut'              => $statut,
            'teleprospecteur_id'  => $teleprospecteurId ?? ($this->defaults['teleprospecteur_id'] ?? null),
            'commercial_id'       => $commercialId ?? ($this->defaults['commercial_id'] ?? null),
            'date_premier_contact' => $this->parseDate($get('date_premier_contact')),
            'rappel_planifie_at'  => $this->parseDate($get('rappel_planifie_at')),
            'description'         => $get('description'),

            // ── Dirigeant ────────────────────────────────────────────
            'dirigeant_nom'       => $get('dirigeant_nom'),
            'dirigeant_prenom'    => $get('dirigeant_prenom'),
            'dirigeant_fonction'  => $get('dirigeant_fonction'),
            'dirigeant_telephone' => $this->cleanTelephone($get('dirigeant_telephone')),
            'dirigeant_email'     => $get('dirigeant_email'),

            // ── CSE ──────────────────────────────────────────────────
            'cse_secretaire_nom'         => $get('cse_secretaire_nom'),
            'cse_secretaire_prenom'      => $get('cse_secretaire_prenom'),
            'cse_secretaire_tel_direct'  => $this->cleanTelephone($get('cse_secretaire_tel_direct')),
            'cse_secretaire_tel_perso'   => $this->cleanTelephone($get('cse_secretaire_tel_perso')),
            'cse_secretaire_email_pro'   => $get('cse_secretaire_email_pro'),
            'cse_secretaire_email_perso' => $get('cse_secretaire_email_perso'),
            'cse_tresorier_nom'          => $get('cse_tresorier_nom'),
            'cse_tresorier_prenom'       => $get('cse_tresorier_prenom'),
            'cse_tresorier_tel_direct'   => $this->cleanTelephone($get('cse_tresorier_tel_direct')),
            'cse_tresorier_tel_perso'    => $this->cleanTelephone($get('cse_tresorier_tel_perso')),
            'cse_tresorier_email_pro'    => $get('cse_tresorier_email_pro'),
            'cse_tresorier_email_perso'  => $get('cse_tresorier_email_perso'),
            'cse_nb_elus'                => $this->cleanInt($get('cse_nb_elus')),
            'cse_date_fin_mandat'        => $this->parseDate($get('cse_date_fin_mandat')),
            'cse_existence_juridique'    => $this->cleanBool($get('cse_existence_juridique')),
            'cse_notes'                  => $get('cse_notes'),

            // ── Syndicat ─────────────────────────────────────────────
            'syndicat_appartenance'         => $get('syndicat_appartenance'),
            'syndicat_nom_organisation'     => $get('syndicat_nom_organisation'),
            'syndicat_responsable_nom'      => $get('syndicat_responsable_nom'),
            'syndicat_responsable_prenom'   => $get('syndicat_responsable_prenom'),
            'syndicat_responsable_fonction' => $get('syndicat_responsable_fonction'),
            'syndicat_tel_direct'           => $this->cleanTelephone($get('syndicat_tel_direct')),
            'syndicat_tel_perso'            => $this->cleanTelephone($get('syndicat_tel_perso')),
            'syndicat_email_pro'            => $get('syndicat_email_pro'),
            'syndicat_email_perso'          => $get('syndicat_email_perso'),
            'syndicat_perimetre'            => $get('syndicat_perimetre'),
            'syndicat_notes'                => $get('syndicat_notes'),
        ];

        return array_filter($data, fn($v) => $v !== null && $v !== '');
    }

    // ── Resolvers ────────────────────────────────────────────────────
    protected function resolveStatut(?string $value): ?string
    {
        if (empty($value)) return null;

        $map = [
            'ac'          => ProspectStatut::AC->value,
            'à contacter' => ProspectStatut::AC->value,
            'a contacter' => ProspectStatut::AC->value,
            'nr'          => ProspectStatut::STD_NR->value,
            'non répondu' => ProspectStatut::STD_NR->value,
            'ko'          => ProspectStatut::KO->value,
            'refus'       => ProspectStatut::KO->value,
            'qf'          => ProspectStatut::QF->value,
            'qualifié'    => ProspectStatut::QF->value,
        ];

        $lower = mb_strtolower(trim($value));
        return $map[$lower] ?? null;
    }

    protected function resolveType(?string $value): ?string
    {
        if (empty($value)) return null;
        $lower = mb_strtolower(trim($value));

        foreach (OrganizationType::cases() as $case) {
            if (str_contains($lower, mb_strtolower($case->value))) {
                return $case->value;
            }
        }
        return null;
    }

    protected function resolveUserId(?string $name): ?int
    {
        if (empty($name)) return null;

        $name  = trim((string) $name);
        $parts = preg_split('/\s+/', $name);

        $query = User::query()->whereIn(
            'role_cache',
            ['teleprospecteur', 'commercial', 'team_leader', 'administrateur']
        );

        if (count($parts) >= 2) {
            $query->where(function ($q) use ($parts) {
                $q->where(function ($q2) use ($parts) {
                    $q2->where('prenom', 'like', "%{$parts[0]}%")
                        ->where('nom', 'like', "%{$parts[1]}%");
                })->orWhere(function ($q2) use ($parts) {
                    $q2->where('nom', 'like', "%{$parts[0]}%")
                        ->where('prenom', 'like', "%{$parts[1]}%");
                });
            });
        } else {
            $query->where(function ($q) use ($name) {
                $q->where('nom', 'like', "%{$name}%")
                    ->orWhere('prenom', 'like', "%{$name}%");
            });
        }

        return $query->value('id');
    }

    // ── Crée automatiquement le User s'il n'existe pas ─────────────────
    // Retente d'abord resolveUserId() (recherche large par nom + rôles
    // autorisés). Si rien n'est trouvé, crée un compte avec le rôle
    // demandé (nom/prénom/role_cache servent de clé d'unicité, donc une
    // seule création même si le même nom apparaît sur des dizaines de
    // lignes ou sur plusieurs feuilles/imports).
    protected function resolveOrCreateUserId(?string $name, string $roleCacheSiNouveau): ?int
    {
        if (empty($name)) return null;

        $existingId = $this->resolveUserId($name);
        if ($existingId) return $existingId;

        [$prenom, $nom] = $this->splitNomPrenom($name);

        if (empty($nom) && empty($prenom)) {
            $this->rowWarnings[] = "Avertissement : \"{$name}\" n'a pas pu être interprété comme un nom — "
                . "valeur par défaut du formulaire appliquée à la place.";
            return null;
        }

        $user = User::firstOrCreate(
            [
                'nom'        => $nom,
                'prenom'     => $prenom,
                'role_cache' => $roleCacheSiNouveau,
            ],
            [
                'email'    => $this->generatePlaceholderEmail($prenom, $nom),
                'password' => Str::random(32),
                'actif'    => false,
            ]
        );

        if ($user->wasRecentlyCreated) {
            $label = User::ROLES[$roleCacheSiNouveau] ?? $roleCacheSiNouveau;
            $this->rowWarnings[] = "Info : \"{$name}\" introuvable en base — nouveau compte {$label} "
                . "créé automatiquement (#{$user->id}, email placeholder, compte inactif) — à vérifier/compléter manuellement.";
        }

        return $user->id;
    }

    protected function splitNomPrenom(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name));
        $parts = array_filter($parts, fn($p) => $p !== '');
        $parts = array_values($parts);

        if (count($parts) >= 2) {
            $prenom = $parts[0];
            $nom    = implode(' ', array_slice($parts, 1));
        } else {
            $prenom = null;
            $nom    = $parts[0] ?? null;
        }

        return [$prenom, $nom];
    }

    protected function generatePlaceholderEmail(?string $prenom, ?string $nom): string
    {
        $slug = Str::slug(trim(($prenom ?? '') . ' ' . ($nom ?? '')), '.');
        $slug = $slug !== '' ? $slug : 'utilisateur';

        return "{$slug}." . Str::random(6) . '@import.local';
    }

    protected function extractDepartement(?string $value): ?string
    {
        if (empty($value)) return null;
        if (preg_match('/^\d{2,3}$/', trim($value))) return trim($value);
        return null;
    }

    protected function parseDate(mixed $value): ?string
    {
        if (empty($value)) return null;
        try {
            if ($value instanceof \DateTime) return $value->format('Y-m-d H:i:s');
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                    ->format('Y-m-d H:i:s');
            }
            return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception) {
            return null;
        }
    }

    protected function cleanCodePostal(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        $digits = preg_replace('/\D/', '', (string) $value);
        return $digits !== '' ? str_pad($digits, 5, '0', STR_PAD_LEFT) : null;
    }

    protected function cleanTelephone(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        $clean = preg_replace('/\s+/', ' ', trim((string) $value));
        return $clean !== '' ? $clean : null;
    }

    protected function cleanSiret(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        $digits = preg_replace('/\D/', '', (string) $value);
        if (strlen($digits) === 14 || strlen($digits) === 9) return $digits;
        return $digits !== '' ? str_pad($digits, 14, '0', STR_PAD_LEFT) : null;
    }

    // ── Gère désormais les tranches ("500 à 999", "3-5") en prenant
    // le milieu de la fourchette, au lieu de concaténer les chiffres.
    protected function cleanInt(mixed $value): ?int
    {
        if ($value === null || $value === '') return null;

        $str = trim((string) $value);

        if (preg_match('/(\d[\d\s]*)\s*(?:à|a|-|–|—|to)\s*(\d[\d\s]*)$/ui', $str, $m)) {
            $low  = (int) preg_replace('/\D/', '', $m[1]);
            $high = (int) preg_replace('/\D/', '', $m[2]);
            if ($high > 0) {
                return intdiv($low + $high, 2);
            }
        }

        $digits = preg_replace('/[^\d]/', '', $str);
        return $digits !== '' ? (int) $digits : null;
    }

    // ── Tolère désormais les caractères parasites en fin de valeur
    // (ex: "1 940 346 800,00 €." avec un point après le symbole monétaire).
    protected function cleanDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;

        $str = (string) $value;
        $str = preg_replace('/\s+/', '', $str);
        $str = preg_replace('/[^\d,.\-]/', '', $str);
        $str = str_replace(',', '.', $str);

        if (preg_match('/-?\d+(\.\d+)?/', $str, $m)) {
            return $m[0];
        }

        return null;
    }

    // ── Nouveau : nettoyage booléen ───────────────────────────────────
    protected function cleanBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') return null;
        $lower = mb_strtolower(trim((string) $value));
        if (in_array($lower, ['1', 'oui', 'yes', 'true', 'vrai'])) return true;
        if (in_array($lower, ['0', 'non', 'no', 'false', 'faux'])) return false;
        return null;
    }

    protected function getResult(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors'  => $this->errors,
        ];
    }
}
