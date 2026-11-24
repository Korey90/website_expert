<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

final class PublicAiMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:2', 'max:'.config('ai_assistant.max_question_chars')],
            'locale' => ['required', 'in:en,pl,pt'],
            'client_message_id' => ['required', 'uuid'],
            'website' => ['nullable', 'max:0'], // honeypot
        ];
    }
}
