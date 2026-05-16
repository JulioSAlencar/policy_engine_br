<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'ip_address',
        'user_agent',
        'properties',
        'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Registra uma ação do sistema. Nunca lança exceção: uma falha de
     * auditoria não pode quebrar a ação principal do usuário.
     */
    public static function record(string $action, string $description, ?Model $subject = null, array $properties = []): void
    {
        rescue(function () use ($action, $description, $subject, $properties) {
            static::create([
                'user_id'      => Auth::id(),
                'action'       => $action,
                'description'  => $description,
                'subject_type' => $subject ? $subject::class : null,
                'subject_id'   => $subject?->getKey(),
                'ip_address'   => request()->ip(),
                'user_agent'   => substr((string) request()->userAgent(), 0, 1000),
                'properties'   => $properties ?: null,
                'created_at'   => now(),
            ]);
        }, report: false);
    }
}
