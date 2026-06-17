<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_groupes', function (Blueprint $table) {
            $table->id();
            $table->string('model_type')->default('prospect');
            $table->string('code');
            $table->string('label');
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['model_type', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_groupes');
    }
};
