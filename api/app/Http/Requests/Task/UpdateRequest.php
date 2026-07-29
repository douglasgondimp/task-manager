<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRequest extends FormRequest
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
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status'      => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority'    => ['sometimes', Rule::enum(TaskPriority::class)],
            'due_date'    => ['sometimes', 'date'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (empty($this->validated())) {
                    $validator->errors()->add(
                        'request',
                        'É necessário informar pelo menos um campo para atualização.'
                    );
                }
            }
        ];
    }

    public function messages()
    {
        $statuses   = implode(', ', array_column(TaskStatus::cases(), 'value'));
        $priorities = implode(', ', array_column(TaskPriority::cases(), 'value'));

        return [
            'title.max'     => "Limite de caracteres atingidos. Max: :value",
            'status.enum'   => "Status inválido. Valores aceitos: {$statuses}.",
            'prioriry.enum' => "Prioridade inválida. Valores aceitos: {$priorities}",
            'due_date.date' => "Formato de data inválido. Formato válido: Y-m-d"
        ];
    }
}
