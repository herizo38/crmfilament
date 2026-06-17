<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PartnerImportBatch
 *
 * Represents a batch of partner imports. Similar to ProspectImportBatch,
 * it keeps track of import metadata and allows grouping multiple
 * PartnerImportRow records. Using batches helps monitor the progress
 * of large partner imports and assists with error reporting.
 */
class PartnerImportBatch extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'partner_import_batches';

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
        return $this->hasMany(PartnerImportRow::class, 'batch_id');
    }
}