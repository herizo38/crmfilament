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
        Schema::create('partner_import_batches', function (Blueprint $table) {
            $table->id();
            // Name of the file used for the import
            $table->string('filename');
            // Optional sheet name within the file
            $table->string('sheet_name')->nullable();
            // User who triggered the import
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            // Total number of rows processed
            $table->integer('rows_total')->default(0);
            // Number of rows successfully imported
            $table->integer('rows_imported')->default(0);
            // Number of rows skipped during the import
            $table->integer('rows_skipped')->default(0);
            // Number of rows that failed to import
            $table->integer('rows_failed')->default(0);
            // Timestamp when the import completed
            $table->timestamp('completed_at')->nullable();
            // JSON field to store errors encountered during the import
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
        Schema::dropIfExists('partner_import_batches');
    }
};