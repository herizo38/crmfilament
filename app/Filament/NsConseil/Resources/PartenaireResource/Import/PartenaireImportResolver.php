<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;

class PartenaireImportResolver
{
    public static function importFile(string $filePath, array $defaults = []): array
    {
        $results = [];

        $spreadsheet = IOFactory::load($filePath);

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $sheetName = $worksheet->getTitle();

            // Ignorer les feuilles vides ou sans données
            $rows = $worksheet->toArray();

            if (empty($rows) || count($rows) < 2) {
                $results[$sheetName] = [
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'errors' => ["Feuille '$sheetName' ignorée (vide ou sans données)"]
                ];
                continue;
            }

            // Vérifier si la feuille contient "Raison sociale"
            $hasRaisonSociale = false;
            foreach ($rows as $row) {
                foreach ($row as $cell) {
                    if (is_string($cell) && stripos($cell, 'Raison sociale') !== false) {
                        $hasRaisonSociale = true;
                        break 2;
                    }
                }
            }

            if (!$hasRaisonSociale) {
                $results[$sheetName] = [
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'errors' => ["Feuille '$sheetName' ignorée - ne contient pas de données partenaires"]
                ];
                continue;
            }

            // Importer la feuille
            $importer = new PartenaireImporter();
            $results[$sheetName] = $importer->import($rows, $sheetName, $defaults);
        }

        return $results;
    }
}