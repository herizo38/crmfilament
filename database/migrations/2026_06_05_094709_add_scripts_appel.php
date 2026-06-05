<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scripts_appel', function (Blueprint $table) {
            $table->id();

            // Identification
            $table->string('titre');
            $table->string('slug')->unique()->comment('accroche|decouverte|argumentaire|objections|closing');

            // Ciblage : à quel type de contact s'applique ce script
            // null = universel (tous types)
            $table->string('type_contact')->nullable()
                ->comment('artisan|partenaire|particulier|prospect|null=tous');

            // Onglet auquel appartient ce script
            $table->enum('onglet', [
                'accroche',
                'decouverte',
                'argumentaire',
                'objections',
                'closing',
            ])->default('accroche');

            // Contenu principal (Markdown ou texte)
            $table->text('contenu')->nullable();

            // Conseil / tip affiché sous le script
            $table->text('conseil')->nullable();

            // Variables dynamiques utilisables dans le contenu :
            // {contact_nom}, {contact_prenom}, {commercial_nom}, etc.
            // Stockées comme JSON pour documentation
            $table->json('variables_disponibles')->nullable();

            // Objections : tableau JSON [{question, reponse}]
            $table->json('objections')->nullable();

            // KPIs affichés dans l'argumentaire [{valeur, label}]
            $table->json('kpis')->nullable();

            // Actif ou archivé
            $table->boolean('actif')->default(true);

            // Ordre d'affichage dans l'onglet
            $table->unsignedSmallInteger('ordre')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['onglet', 'type_contact', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scripts_appel');
    }
};
