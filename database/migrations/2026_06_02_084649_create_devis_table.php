<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devis', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique()->comment('Format DEV-AAAA-NNNN');
            $table->foreignId('ticket_id')->constrained()->onDelete('restrict');
            $table->foreignId('artisan_id')->constrained()->onDelete('restrict');
            $table->foreignId('contact_particulier_id')->constrained('contact_particuliers')->onDelete('restrict');

            // Contenu et finances
            $table->json('lignes')->comment('[{libelle, quantite, prix_unitaire_ht, taux_tva}]');
            $table->decimal('remise_montant', 10, 2)->default(0);
            $table->decimal('remise_pourcentage', 5, 2)->default(0);
            $table->string('conditions_paiement')->default('solde_intervention');
            $table->text('notes')->nullable();

            // Dates
            $table->date('date_validite')->comment('Par défaut J+30');
            $table->date('date_emission')->nullable();
            $table->timestamp('date_acceptation_refus')->nullable();

            // Statuts et suivi
            $table->string('statut')->default('brouillon');
            $table->string('mode_acceptation')->nullable()->comment('signature_electronique / appel / email');

            // Totaux calculés (dénormalisés pour performances)
            $table->decimal('total_ht', 10, 2)->default(0);
            $table->decimal('montant_tva', 10, 2)->default(0);
            $table->decimal('total_ttc', 10, 2)->default(0);

            $table->timestamps();
            $table->softDeletes();
        });

        // Ajouter les indexes après la création de la table pour éviter les doublons
        $this->addIndexes();
    }

    /**
     * Ajoute les indexes avec vérification d'existence
     */
    private function addIndexes(): void
    {
        $table = 'devis';
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        // Index simple sur statut
        if (!$this->indexExists('devis_statut_index', $table)) {
            Schema::table($table, function (Blueprint $table) {
                $table->index('statut');
            });
        }

        // Index simple sur date_validite
        if (!$this->indexExists('devis_date_validite_index', $table)) {
            Schema::table($table, function (Blueprint $table) {
                $table->index('date_validite');
            });
        }

        // Index composite sur artisan_id et statut
        if (!$this->indexExists('devis_artisan_id_statut_index', $table)) {
            Schema::table($table, function (Blueprint $table) {
                $table->index(['artisan_id', 'statut']);
            });
        }

        // Index composite sur contact_particulier_id et statut
        if (!$this->indexExists('devis_contact_particulier_id_statut_index', $table)) {
            Schema::table($table, function (Blueprint $table) {
                $table->index(['contact_particulier_id', 'statut']);
            });
        }
    }

    /**
     * Vérifie si un index existe (multi-moteurs)
     */
    private function indexExists(string $indexName, string $table): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        try {
            switch ($driver) {
                case 'mysql':
                    $result = $connection->select("
                        SELECT COUNT(*) as count
                        FROM information_schema.statistics
                        WHERE table_schema = DATABASE()
                        AND table_name = ?
                        AND index_name = ?
                    ", [$table, $indexName]);
                    return $result[0]->count > 0;

                case 'pgsql':
                    $result = $connection->select("
                        SELECT COUNT(*) as count
                        FROM pg_indexes
                        WHERE schemaname = 'public'
                        AND tablename = ?
                        AND indexname = ?
                    ", [$table, $indexName]);
                    return $result[0]->count > 0;

                case 'sqlite':
                    $result = $connection->select("
                        SELECT COUNT(*) as count
                        FROM sqlite_master
                        WHERE type = 'index'
                        AND tbl_name = ?
                        AND name = ?
                    ", [$table, $indexName]);
                    return $result[0]->count > 0;

                default:
                    return false;
            }
        } catch (\Exception $e) {
            // Si la table n'existe pas encore, retourner false
            return false;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devis');
    }
};
