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
        Schema::dropIfExists('factures');

        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique()->comment('Format FAC-AAAA-NNNN — séquence chronologique obligatoire');
            $table->foreignId('bon_de_commande_id')->constrained()->onDelete('restrict');
            $table->foreignId('ticket_id')->constrained()->onDelete('restrict');
            $table->foreignId('artisan_id')->constrained()->onDelete('restrict');
            $table->foreignId('contact_particulier_id')->constrained('contact_particuliers')->onDelete('restrict');

            // Contenu
            $table->json('lignes')->comment('[{libelle, quantite, prix_unitaire_ht, taux_tva}]');

            // Totaux
            $table->decimal('total_ht', 10, 2)->default(0);
            $table->decimal('montant_tva', 10, 2)->default(0);
            $table->decimal('total_ttc', 10, 2)->default(0);

            // Paiements et acomptes
            $table->decimal('acompte_deja_verse', 10, 2)->nullable();
            $table->decimal('solde_restant_du', 10, 2)->default(0);

            // Dates
            $table->date('date_emission')->nullable();
            $table->date('date_echeance')->nullable()->comment('Date limite de règlement (obligatoire)');
            $table->date('date_paiement_effectif')->nullable()->comment('À la réception du règlement');

            // Modes et statuts
            $table->string('mode_paiement')->nullable()->comment('virement / cb / cheque / especes');
            $table->string('statut_paiement')->default('en_attente')->comment('en_attente / partiel / paye / en_retard / litigieux');
            $table->string('conditions_paiement')->nullable();

            // Pénalités et avoir
            $table->decimal('penalites_retard', 10, 2)->default(0);
            $table->foreignId('avoir_id')->nullable()->constrained('factures')->onDelete('set null')->comment('Avoir associé');

            // Documents
            $table->string('fichier_pdf')->nullable()->comment('Path vers le PDF généré');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Index (définis directement dans le create)
            $table->index('statut_paiement');
            $table->index('date_echeance');
            $table->index(['artisan_id', 'statut_paiement']);
            $table->index(['contact_particulier_id', 'statut_paiement']);
            $table->index('bon_de_commande_id');
            $table->index('avoir_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
