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
        Schema::create('partner_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')
                ->constrained('partner_import_batches')
                ->cascadeOnDelete();
            // Index of the row in the original spreadsheet or file
            $table->integer('row_index');
            // Raw data from the import, stored as JSON
            $table->json('raw_data');
            // Status of the row: imported, skipped, or failed
            $table->string('status')->default('imported');
            // Optional error message if the row failed to import
            $table->text('error_message')->nullable();
            // Reference to the created partner record, if any
            $table->foreignId('partner_id')
                ->nullable()
                ->constrained('partenaires')
                ->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_import_rows');
    }
};