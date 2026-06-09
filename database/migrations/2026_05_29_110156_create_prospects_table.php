<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\ProspectStatut;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('type_pressenti')->nullable();
            $table->string('departement', 3)->nullable();
            $table->string('telephone')->nullable();
            $table->string('telephone_alt')->nullable();
            $table->string('email')->nullable();
            $table->text('adresse')->nullable();
            $table->string('code_postal', 5)->nullable();
            $table->string('ville')->nullable();
            $table->string('siret', 14)->nullable();
            $table->string('secteur_activite')->nullable();
            $table->integer('nb_salaries')->nullable();
            $table->decimal('chiffre_affaires', 15, 2)->nullable();
            $table->string('statut')->default(ProspectStatut::AC->value);
            $table->foreignId('teleprospecteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('commercial_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_premier_contact')->nullable();
            $table->dateTime('rappel_planifie_at')->nullable();
            $table->string('interlocuteur_nom')->nullable();
            $table->string('interlocuteur_fonction')->nullable();
            $table->string('interlocuteur_telephone')->nullable();
            $table->string('interlocuteur_email')->nullable();
            $table->text('description')->nullable();
            $table->text('motif_ko')->nullable();
            $table->boolean('qf_valide')->default(false);
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('qf_valide_at')->nullable();
            // ── Informations CSE (si type_pressenti = CSE) ──────────────────
            $table->string('cse_secretaire_nom')->nullable();
            $table->string('cse_secretaire_prenom')->nullable();
            $table->string('cse_secretaire_tel_direct')->nullable();
            $table->string('cse_secretaire_tel_perso')->nullable();
            $table->string('cse_secretaire_email_pro')->nullable();
            $table->string('cse_secretaire_email_perso')->nullable();
            $table->string('cse_tresorier_nom')->nullable();
            $table->string('cse_tresorier_prenom')->nullable();
            $table->string('cse_tresorier_tel_direct')->nullable();
            $table->string('cse_tresorier_tel_perso')->nullable();
            $table->string('cse_tresorier_email_pro')->nullable();
            $table->string('cse_tresorier_email_perso')->nullable();
            $table->integer('cse_nb_elus')->nullable();
            $table->date('cse_date_fin_mandat')->nullable();
            $table->boolean('cse_existence_juridique')->default(false);
            $table->text('cse_notes')->nullable();

            // ── Informations Syndicat (si type_pressenti = Syndicat) ────────
            $table->string('syndicat_appartenance')->nullable();
            $table->string('syndicat_nom_organisation')->nullable();
            $table->string('syndicat_responsable_nom')->nullable();
            $table->string('syndicat_responsable_prenom')->nullable();
            $table->string('syndicat_responsable_fonction')->nullable();
            $table->string('syndicat_tel_direct')->nullable();
            $table->string('syndicat_tel_perso')->nullable();
            $table->string('syndicat_email_pro')->nullable();
            $table->string('syndicat_email_perso')->nullable();
            $table->text('syndicat_perimetre')->nullable();
            $table->text('syndicat_notes')->nullable();

            // ── Dirigeant (commun à tous les types) ─────────────────────────
            $table->string('dirigeant_nom')->nullable();
            $table->string('dirigeant_prenom')->nullable();
            $table->string('dirigeant_fonction')->nullable();
            $table->string('dirigeant_telephone')->nullable();
            $table->string('dirigeant_email')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
