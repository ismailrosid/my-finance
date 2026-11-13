<?php

namespace App\Traits;

use App\Models\RevisionInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait Auditable
{
    protected static bool $auditingEnabled = true;
    protected static array $auditColumnsCache = [];

    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->recordRevision(RevisionInfo::TYPE_CREATE);
        });

        static::updated(function ($model) {
            $model->recordRevision(RevisionInfo::TYPE_UPDATE);
        });

        static::deleted(function ($model) {
            $model->recordRevision(RevisionInfo::TYPE_DELETE);
        });
    }

    /**
     * Temporarily disable auditing for bulk operations or system seeding.
     */
    public static function withoutAuditing(callable $callback): mixed
    {
        $previous = static::$auditingEnabled;
        static::$auditingEnabled = false;

        try {
            return $callback();
        } finally {
            static::$auditingEnabled = $previous;
        }
    }

    /**
     * Get shadow audit table name.
     */
    public function getAuditTable(): string
    {
        return property_exists($this, 'auditTable') && !empty($this->auditTable)
            ? $this->auditTable
            : $this->getTable() . '_aud';
    }

    /**
     * Cache table columns for fast snapshot insertion.
     */
    public static function getAuditTableColumns(string $table): array
    {
        return static::$auditColumnsCache[$table] ??= Schema::getColumnListing($table);
    }

    /**
     * Record a snapshot of the current model state into the shadow audit table.
     */
    public function recordRevision(int $revisionType): ?RevisionInfo
    {
        if (!static::$auditingEnabled) {
            return null;
        }

        $auditTable = $this->getAuditTable();
        if (!Schema::hasTable($auditTable)) {
            return null;
        }

        try {
            $userId = auth()->id() ?? ($this->getAttribute('user_id') ?? null);
            $ip = request() ? request()->ip() : null;
            $userAgent = request() ? substr(request()->userAgent() ?? '', 0, 500) : null;

            $revision = RevisionInfo::create([
                'user_id'    => $userId,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'created_at' => now(),
            ]);

            $columns = static::getAuditTableColumns($auditTable);
            $attributes = $this->getAttributes();

            $data = array_intersect_key($attributes, array_flip($columns));
            $data['id'] = $this->getKey();
            $data['revision_id'] = $revision->id;
            $data['revision_type'] = $revisionType;

            DB::table($auditTable)->insert($data);

            return $revision;
        } catch (\Throwable $e) {
            // Log warning or rethrow if strict; we don't want audit failure to silently corrupt business transactions
            report($e);
            return null;
        }
    }

    /**
     * Get full temporal revision history for this record.
     */
    public function auditHistory()
    {
        return static::getAuditHistoryFor($this->getKey());
    }

    /**
     * Query shadow audit history for a specific record ID.
     */
    public static function getAuditHistoryFor(int|string $id)
    {
        $instance = new static;
        $auditTable = $instance->getAuditTable();

        return DB::table($auditTable)
            ->join('revision_info', "{$auditTable}.revision_id", '=', 'revision_info.id')
            ->leftJoin('users', 'revision_info.user_id', '=', 'users.id')
            ->select([
                "{$auditTable}.*",
                'revision_info.user_id as rev_user_id',
                'revision_info.ip_address as rev_ip_address',
                'revision_info.user_agent as rev_user_agent',
                'revision_info.created_at as rev_created_at',
                'users.name as rev_user_name',
                'users.email as rev_user_email',
            ])
            ->where("{$auditTable}.id", $id)
            ->orderBy("{$auditTable}.audit_id", 'desc')
            ->get();
    }
}
