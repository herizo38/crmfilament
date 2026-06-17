<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statut_phonings', function (Blueprint $table) {
            $table->string('groupe')->nullable()->after('model_type');
            $table->string('groupe_label')->nullable()->after('groupe');
            $table->text('action_immediate')->nullable()->after('description');
            $table->boolean('note_obligatoire')->default(false)->after('action_immediate');
            $table->unsignedTinyInteger('delai_rappel_jours')->nullable()->after('note_obligatoire');
            $table->boolean('prioritaire')->default(false)->after('delai_rappel_jours');
            $table->string('fiche_type')->nullable()->after('prioritaire');
            $table->boolean('retire_de_file')->default(false)->after('fiche_type');
        });
    }

    public function down(): void
    {
        Schema::table('statut_phonings', function (Blueprint $table) {
            $table->dropColumn([
                'groupe',
                'groupe_label',
                'action_immediate',
                'note_obligatoire',
                'delai_rappel_jours',
                'prioritaire',
                'fiche_type',
                'retire_de_file',
            ]);
        });
    }
};
