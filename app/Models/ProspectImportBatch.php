<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ProspectImportBatch
 *
 * This model represents a batch of prospect imports. Each batch holds
 * metadata about the import (file name, sheet name, user who initiated
 * the import) and aggregates multiple ProspectImportRow records. Using
 * batches helps track large imports and associate rows with a common
 * context.
 */
class ProspectImportBatch extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'prospect_import_batches';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'filename',
        'sheet_name',
        'user_id',
        'rows_total',
        'rows_imported',
        'rows_skipped',
        'rows_failed',
        'completed_at',
        'errors',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'completed_at' => 'datetime',
        'errors' => 'array',
        'rows_total' => 'integer',
        'rows_imported' => 'integer',
        'rows_skipped' => 'integer',
        'rows_failed' => 'integer',
    ];

    /**
     * Get the rows associated with this batch.
     */
    public function rows()
    {
        return $this->hasMany(ProspectImportRow::class, 'batch_id');
    }
}