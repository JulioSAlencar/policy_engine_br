<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_identifier',
        'input_text',
        'output_text',
        'url_source',
        'captured_at',
        'status',
        'has_sensitive_data',
        'risk_level',
        'leak_type',
        'gemini_justification',
        'gemini_raw_response',
        'processed_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'processed_at' => 'datetime',
        'has_sensitive_data' => 'boolean',
        'gemini_raw_response' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByRisk($query, string $level)
    {
        return $query->where('risk_level', $level);
    }

    public function getRiskColorAttribute(): string
    {
        return match($this->risk_level) {
            'critical' => 'red',
            'high'     => 'orange',
            'medium'   => 'yellow',
            'low'      => 'green',
            default    => 'gray',
        };
    }

    /**
     * Identifica o agente de IA a partir da URL de origem capturada.
     */
    public function getAiAgentNameAttribute(): string
    {
        $url = strtolower((string) $this->url_source);

        return match (true) {
            str_contains($url, 'chatgpt') || str_contains($url, 'openai') => 'ChatGPT',
            str_contains($url, 'claude')                                  => 'Claude AI',
            str_contains($url, 'gemini')                                  => 'Google Gemini',
            default                                                       => 'Agente de IA',
        };
    }
}
