<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_statuts', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->string('code');
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('couleur')->default('gray');
            $table->string('icone')->nullable();
            $table->json('transitions')->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('is_terminal')->default(false);
            $table->boolean('is_archive')->default(false);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['model_type', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_statuts');
    }
};
