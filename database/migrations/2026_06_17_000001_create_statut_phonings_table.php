<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statut_phonings', function (Blueprint $table) {
            $table->id();
            $table->string('model_type'); // prospect, partenaire, opportunite, client
            $table->string('code');
            $table->string('label');
            $table->string('description')->nullable();
            $table->string('couleur')->default('gray');
            $table->string('icone')->default('📞');
            $table->integer('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['model_type', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statut_phonings');
    }
};
