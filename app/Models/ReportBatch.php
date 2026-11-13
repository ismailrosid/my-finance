<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportBatch extends Model
{
    protected $table = 'report_batches';

    protected $fillable = [
        'user_id',
        'report_type',
        'report_name',
        'parameters',
        'file_path',
        'file_size',
        'total_records',
        'status',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'parameters'    => 'array',
        'file_size'     => 'integer',
        'total_records' => 'integer',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    public function getStatusBadgeVariantAttribute(): string
    {
        return match ($this->status) {
            'completed'  => 'green',
            'processing' => 'yellow',
            'failed'     => 'red',
            default      => 'gray',
        };
    }

    public function getParametersSummaryAttribute(): string
    {
        $params = $this->parameters ?? [];
        $parts = [];

        if (!empty($params['date_from']) && !empty($params['date_to'])) {
            $parts[] = date('d M Y', strtotime($params['date_from'])) . ' - ' . date('d M Y', strtotime($params['date_to']));
        } elseif (!empty($params['date_from'])) {
            $parts[] = 'From ' . date('d M Y', strtotime($params['date_from']));
        } elseif (!empty($params['date_to'])) {
            $parts[] = 'Until ' . date('d M Y', strtotime($params['date_to']));
        } else {
            $parts[] = 'All Time';
        }

        if (!empty($params['target_user_name'])) {
            $parts[] = 'User: ' . $params['target_user_name'];
        }

        return implode(' • ', $parts);
    }
}
