<?php

namespace App\Http\Requests\ProjectTasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', Rule::enum(TaskStatus::class)],
            'priority'    => ['nullable', Rule::enum(TaskPriority::class)],
            'due_date'    => ['nullable', 'date'],
        ];
    }

    public function messages()
    {
        $statuses   = implode(', ', array_column(TaskStatus::cases(), 'value'));
        $priorities = implode(', ', array_column(TaskPriority::cases(), 'value'));

        return [
            'title.required'      => "Título da tarefa é obrigatório.",
            'status.enum'         => "Status inválido. Valores aceitos: {$statuses}.",
            'prioriry.enum'       => "Prioridade inválida. Valores aceitos: {$priorities}",
            'due_date.date'       => "Data de validade informada incorretamente. Valor aceito: Y-m-d",
        ];
    }
}
