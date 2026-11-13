<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevisionInfo extends Model
{
    public const UPDATED_AT = null; // Revision info is immutable

    public const TYPE_CREATE = 0;
    public const TYPE_UPDATE = 1;
    public const TYPE_DELETE = 2;

    protected $table = 'revision_info';

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function typeName(int $type): string
    {
        return match ($type) {
            self::TYPE_CREATE => 'CREATE',
            self::TYPE_UPDATE => 'UPDATE',
            self::TYPE_DELETE => 'DELETE',
            default           => 'UNKNOWN',
        };
    }

    public static function typeBadgeClass(int $type): string
    {
        return match ($type) {
            self::TYPE_CREATE => 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20',
            self::TYPE_UPDATE => 'bg-amber-500/10 text-amber-500 border border-amber-500/20',
            self::TYPE_DELETE => 'bg-rose-500/10 text-rose-500 border border-rose-500/20',
            default           => 'bg-slate-500/10 text-slate-500 border border-slate-500/20',
        };
    }
}
