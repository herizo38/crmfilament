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
        // Supprimer la table si elle existe pour repartir de zéro
        Schema::dropIfExists('bon_de_commandes');

        Schema::create('bon_de_commandes', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique()->comment('Format BC-AAAA-NNNN');
            $table->foreignId('devis_id')->constrained()->onDelete('restrict');
            $table->foreignId('ticket_id')->constrained()->onDelete('restrict');
            $table->foreignId('artisan_id')->constrained()->onDelete('restrict');
            $table->foreignId('contact_particulier_id')->constrained('contact_particuliers')->onDelete('restrict');

            // Contenu
            $table->json('lignes')->comment('Reprises du devis accepté');
            $table->decimal('montant_total_ttc', 10, 2)->default(0);

            // Acompte
            $table->decimal('acompte_montant', 10, 2)->nullable();
            $table->boolean('acompte_encaisse')->default(false);

            // Planification
            $table->timestamp('date_intervention_prevue')->nullable();
            $table->integer('duree_estimee_heures')->nullable();
            $table->text('instructions_artisan')->nullable()->comment('Accès, outils particuliers…');

            // Conditions
            $table->string('conditions_paiement')->default('solde_intervention');

            // Statuts
            $table->string('statut')->default('en_attente')->comment('en_attente / confirme / en_cours / realise / annule');
            $table->timestamp('date_confirmation')->nullable()->comment('Quand artisan confirme');

            $table->timestamps();
            $table->softDeletes();

            // Index (définis directement dans le create)
            $table->index('statut');
            $table->index('date_intervention_prevue');
            $table->index(['artisan_id', 'statut']);
            $table->index('devis_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bon_de_commandes');
    }
};
