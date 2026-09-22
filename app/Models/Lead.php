<?php

namespace App\Models;

use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    public const STAGES = [
        'new' => 'Novo contato',
        'attending' => 'Em atendimento',
        'quote_requested' => 'Orçamento solicitado',
        'quote_sent' => 'Orçamento enviado',
        'negotiation' => 'Negociação',
        'won' => 'Ganho',
        'lost' => 'Perdido',
    ];

    protected $fillable = ['customer_id', 'assigned_to', 'name', 'company', 'email', 'phone', 'stage', 'origin', 'interest', 'estimated_value', 'probability', 'next_contact_at', 'won_at', 'lost_at', 'lost_reason', 'notes'];

    protected function casts(): array
    {
        return ['estimated_value' => 'decimal:2', 'probability' => 'integer', 'next_contact_at' => 'datetime', 'won_at' => 'datetime', 'lost_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest('event_at')->latest();
    }
}
