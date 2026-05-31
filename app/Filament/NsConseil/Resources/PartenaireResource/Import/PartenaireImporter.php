<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Import;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Models\Partenaire;
use App\Models\User;

class PartenaireImporter
{
    protected array $errors = [];
    protected int $created = 0;
    protected int $updated = 0;
    protected int $skipped = 0;

    protected array $columnMapping = [];
    protected array $defaults = [];

    public static function getRequiredColumns(): array
    {
        return ['Raison sociale', 'CP', 'Ville'];
    }

    public function import(array $rows, string $sourceSheet = '', array $defaults = []): array
    {
        $this->defaults = $defaults;

        // Trouver la ligne d'en-tête (chercher "Raison sociale")
        $headerRowIndex = $this->findHeaderRow($rows);

        if ($headerRowIndex === null) {
            $this->errors[] = "Impossible de trouver la ligne d'en-tête contenant 'Raison sociale'";
            return $this->getResult();
        }

        // Construire le mapping des colonnes
        $this->buildColumnMapping($rows[$headerRowIndex]);

        // Importer les lignes de données (après l'en-tête)
        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            // Vérifier si la ligne est vide
            if ($this->isEmptyRow($row)) {
                continue;
            }

            try {
                $data = $this->mapRow($row);

                if (empty($data['nom'])) {
                    $this->skipped++;
                    $this->errors[] = "Ligne " . ($i + 1) . " : Raison sociale manquante";
                    continue;
                }

                // Rechercher ou créer le partenaire
                $siret = $data['siret'] ?? null;

                if ($siret) {
                    $partenaire = Partenaire::updateOrCreate(['siret' => $siret], $data);
                } else {
                    $partenaire = Partenaire::updateOrCreate(
                        ['nom' => $data['nom'], 'ville' => $data['ville'] ?? null],
                        $data
                    );
                }

                $partenaire->wasRecentlyCreated ? $this->created++ : $this->updated++;

            } catch (\Throwable $e) {
                $this->errors[] = "Ligne " . ($i + 1) . " : " . $e->getMessage();
            }
        }

        return $this->getResult();
    }

    protected function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            if (empty($row))
                continue;

            foreach ($row as $cell) {
                $cell = mb_strtolower(trim((string) $cell));
                if (str_contains($cell, 'raison sociale') || str_contains($cell, 'raison_sociale')) {
                    return $index;
                }
            }
        }
        return null;
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            $cell = trim((string) $cell);
            if (!empty($cell) && $cell !== '') {
                return false;
            }
        }
        return true;
    }

    protected function buildColumnMapping(array $headerRow): void
    {
        $mapping = [
            'conseiller' => ['conseiller'],
            'dpt' => ['dpt', 'departement'],
            'etat' => ['etat', 'statut'],
            'date_evaluation' => ['date de l\'évaluation', 'date_evaluation', 'date'],
            'commentaire' => ['commentaire'],
            'raison_sociale' => ['raison sociale', 'raison_sociale', 'nom', 'entreprise'],
            'adresse' => ['adresse', 'address'],
            'cp' => ['cp', 'code postal', 'code_postal'],
            'ville' => ['ville', 'commune', 'city'],
            'telephone' => ['téléphone', 'telephone', 'tel', 'phone'],
        ];

        $normalizedHeader = array_map(fn($col) => mb_strtolower(trim((string) $col)), $headerRow);

        foreach ($mapping as $field => $possibleNames) {
            foreach ($normalizedHeader as $index => $colName) {
                foreach ($possibleNames as $possibleName) {
                    if (str_contains($colName, $possibleName) || $possibleName === $colName) {
                        $this->columnMapping[$field] = $index;
                        break 2;
                    }
                }
            }
        }
    }

    protected function mapRow(array $row): array
    {
        $getValue = function ($field) use ($row) {
            $index = $this->columnMapping[$field] ?? null;
            if ($index !== null && isset($row[$index])) {
                $value = $row[$index];
                return is_string($value) ? trim($value) : $value;
            }
            return null;
        };

        $cp = $this->cleanCodePostal($getValue('cp'));
        $departement = $cp ? substr($cp, 0, 2) : $this->extractDepartement($getValue('dpt'));

        // Chercher le commercial par nom
        $commercialName = $getValue('conseiller');
        $commercialId = null;
        if ($commercialName) {
            $commercial = User::query()->where('nom', 'like', "%{$commercialName}%")
                ->orWhere('prenom', 'like', "%{$commercialName}%")
                ->first();
            if ($commercial) {
                $commercialId = $commercial->id;
            }
        }

        return array_filter([
            'nom' => $getValue('raison_sociale'),
            'adresse' => $getValue('adresse'),
            'code_postal' => $cp,
            'ville' => $getValue('ville'),
            'departement' => $departement,
            'telephone' => $this->cleanTelephone($getValue('telephone')),
            'commentaire_import' => $getValue('commentaire'),
            'date_evaluation' => $this->parseDate($getValue('date_evaluation')),
            'statut_prospection' => $getValue('etat'),
            // Valeurs par défaut
            'type' => $this->defaults['type'] ?? OrganizationType::EntrepriseDirecte->value,
            'statut' => $this->defaults['statut'] ?? OrganizationStatus::AProspecter->value,
            'commercial_id' => $commercialId ?? $this->defaults['commercial_id'] ?? null,
            'nomenclature_interne' => $this->defaults['nomenclature_interne'] ?? 'ENT_DIRECTE',
            'secteur_activite' => $this->defaults['secteur_activite'] ?? 'Non renseigné',
        ], fn($v) => $v !== null && $v !== '');
    }

    protected function extractDepartement(?string $dptString): ?string
    {
        if (empty($dptString))
            return null;

        // Chercher un code département dans la chaîne (ex: "La Loire-Atlantique" -> "44")
        $departements = [
            'loire-atlantique' => '44',
            'vendée' => '85',
            'ille-et-vilaine' => '35',
            'morbihan' => '56',
            'côtes-d\'armor' => '22',
            'maine-et-loire' => '49',
            'sarthe' => '72',
            'mayenne' => '53',
        ];

        $lower = mb_strtolower($dptString);
        foreach ($departements as $key => $code) {
            if (str_contains($lower, $key)) {
                return $code;
            }
        }

        return null;
    }

    protected function parseDate(mixed $value): ?string
    {
        if (empty($value))
            return null;

        try {
            if ($value instanceof \DateTime) {
                return $value->format('Y-m-d');
            }

            $date = \Carbon\Carbon::parse($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    // ─── Helpers ────────────────────────────────────────────────────

    protected function cleanCodePostal(mixed $value): ?string
    {
        if (empty($value))
            return null;
        $digits = preg_replace('/\D/', '', (string) $value);
        return $digits !== '' ? str_pad($digits, 5, '0', STR_PAD_LEFT) : null;
    }

    protected function cleanTelephone(mixed $value): ?string
    {
        if (empty($value))
            return null;
        $clean = trim((string) $value);
        // Nettoyer les espaces et le 0 initial pour l'affichage
        $clean = preg_replace('/\s+/', ' ', $clean);
        return $clean !== '' ? $clean : null;
    }

    protected function getResult(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }
}