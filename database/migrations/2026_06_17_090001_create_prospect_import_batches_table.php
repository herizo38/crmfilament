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
        Schema::create('prospect_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('sheet_name')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('rows_total')->default(0);
            $table->integer('rows_imported')->default(0);
            $table->integer('rows_skipped')->default(0);
            $table->integer('rows_failed')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospect_import_batches');
    }
};