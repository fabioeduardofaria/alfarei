<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LeadWorkflowService
{
    public function create(array $attributes, User $user): Lead
    {
        return DB::transaction(function () use ($attributes, $user): Lead {
            $lead = Lead::create($this->withStageDates($attributes));
            $this->activity($lead, $user, 'created', 'Oportunidade registrada no CRM.');

            return $lead;
        });
    }

    public function update(Lead $lead, array $attributes, User $user): Lead
    {
        return DB::transaction(function () use ($lead, $attributes, $user): Lead {
            $previousStage = $lead->stage;
            $lead->update($this->withStageDates($attributes, $previousStage));

            if ($previousStage !== $lead->stage) {
                $this->activity($lead, $user, 'stage', 'Etapa alterada de "'.Lead::STAGES[$previousStage].'" para "'.Lead::STAGES[$lead->stage].'".');
            }

            return $lead->fresh();
        });
    }

    public function addActivity(Lead $lead, array $attributes, User $user): void
    {
        $this->activity($lead, $user, $attributes['type'], $attributes['description'], $attributes['event_at'] ?? now());
    }

    public function convertToCustomer(Lead $lead, User $user): Customer
    {
        return DB::transaction(function () use ($lead, $user): Customer {
            if ($lead->customer) {
                return $lead->customer;
            }

            $attributes = ['type' => 'PF', 'name' => $lead->name, 'phone' => $lead->phone, 'customer_group' => 'final', 'active' => true, 'notes' => 'Cliente criado pelo CRM a partir da oportunidade '.$lead->id.'.'];
            $customer = $lead->email ? Customer::firstOrCreate(['email' => $lead->email], $attributes) : Customer::create($attributes);
            $lead->update(['customer_id' => $customer->id]);
            $this->activity($lead, $user, 'converted', 'Oportunidade vinculada ao cliente '.$customer->name.'.');

            return $customer;
        });
    }

    private function withStageDates(array $attributes, ?string $previousStage = null): array
    {
        if (($attributes['stage'] ?? null) === 'won' && $previousStage !== 'won') {
            $attributes['won_at'] = now();
            $attributes['lost_at'] = null;
        }
        if (($attributes['stage'] ?? null) === 'lost' && $previousStage !== 'lost') {
            $attributes['lost_at'] = now();
            $attributes['won_at'] = null;
        }

        return $attributes;
    }

    private function activity(Lead $lead, User $user, string $type, string $description, mixed $eventAt = null): void
    {
        $lead->activities()->create(['user_id' => $user->id, 'type' => $type, 'description' => $description, 'event_at' => $eventAt ?? now()]);
    }
}
