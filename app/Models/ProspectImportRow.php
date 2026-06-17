<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * ProspectImportRow
 *
 * This model represents a single row in a prospect import. Each row
 * holds the raw imported data (as an array), a status indicating
 * whether the row was successfully imported, skipped, or failed, and
 * references back to its batch. After processing, rows can link to
 * the created Prospect record if applicable.
 */
class ProspectImportRow extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'prospect_import_rows';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'batch_id',
        'row_index',
        'raw_data',
        'status',
        'error_message',
        'prospect_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'raw_data' => 'array',
        'row_index' => 'integer',
        'prospect_id' => 'integer',
    ];

    /**
     * Possible statuses for import rows.
     */
    public const STATUS_IMPORTED = 'imported';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_FAILED = 'failed';

    /**
     * Get the batch associated with this row.
     */
    public function batch()
    {
        return $this->belongsTo(ProspectImportBatch::class, 'batch_id');
    }

    /**
     * Get the prospect associated with this row if created.
     */
    public function prospect()
    {
        return $this->belongsTo(Prospect::class, 'prospect_id');
    }
}