<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campagne_phonings', function (Blueprint $table) {

            if (!Schema::hasColumn('campagne_phonings', 'description')) {
                $table->text('description')->nullable()->after('nom');
            }

            if (!Schema::hasColumn('campagne_phonings', 'statut')) {
                $table->string('statut', 20)
                    ->default('brouillon')
                    ->after('description');

                $table->index('statut');
            }

            if (!Schema::hasColumn('campagne_phonings', 'type_entite')) {
                $table->string('type_entite', 20)
                    ->default('prospects')
                    ->after('statut');
            }

            if (!Schema::hasColumn('campagne_phonings', 'criteres')) {
                $table->json('criteres')
                    ->nullable()
                    ->after('type_entite');
            }

            if (!Schema::hasColumn('campagne_phonings', 'date_debut')) {
                $table->date('date_debut')
                    ->nullable()
                    ->after('criteres');
            }

            if (!Schema::hasColumn('campagne_phonings', 'date_fin')) {
                $table->date('date_fin')
                    ->nullable()
                    ->after('date_debut');
            }

            if (!Schema::hasColumn('campagne_phonings', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('date_fin')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('campagne_phonings', function (Blueprint $table) {

            if (Schema::hasColumn('campagne_phonings', 'user_id')) {
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Throwable $e) {
                    // Ignore si la clé étrangère n'existe pas
                }
            }

            $columns = [
                'description',
                'statut',
                'type_entite',
                'criteres',
                'date_debut',
                'date_fin',
                'user_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('campagne_phonings', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (!Schema::hasColumn('campagne_phonings', 'departement')) {
                $table->integer('departement')->nullable();
            }

            if (!Schema::hasColumn('campagne_phonings', 'annee')) {
                $table->string('annee', 4)->nullable();
            }

            if (!Schema::hasColumn('campagne_phonings', 'consultant_id')) {
                $table->unsignedBigInteger('consultant_id')->nullable();
                $table->index('consultant_id');
            }
        });
    }
};
