<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess('crm') ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['note', 'call', 'email', 'meeting', 'task'])],
            'description' => ['required', 'string', 'max:3000'],
            'event_at' => ['nullable', 'date'],
        ];
    }
}
