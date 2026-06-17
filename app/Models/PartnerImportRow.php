<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * PartnerImportRow
 *
 * Represents a single row in a partner import. Each row stores the
 * original data from the spreadsheet, a status flag, optional error
 * message, and a reference to the created Partner (if any). Rows are
 * associated with a PartnerImportBatch for grouping and tracking.
 */
class PartnerImportRow extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'partner_import_rows';

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
        'partner_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'raw_data' => 'array',
        'row_index' => 'integer',
        'partner_id' => 'integer',
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
        return $this->belongsTo(PartnerImportBatch::class, 'batch_id');
    }

    /**
     * Get the partner associated with this row if created.
     */
    public function partner()
    {
        return $this->belongsTo(Partenaire::class, 'partner_id');
    }
}