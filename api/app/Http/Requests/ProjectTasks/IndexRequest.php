<?php

namespace App\Http\Requests\ProjectTasks;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $data = [];
        $createdAt = $this->input('created_at');

        if (is_array($createdAt)) {
            $startDate = $createdAt[0] ?? null;
            $endDate = $createdAt[1] ?? null;

            if ($startDate && ! $endDate) {
                $endDate = $startDate;
            }

            if (! $startDate && $endDate) {
                $startDate = $endDate;
            }

            $data['created_at'] = [$startDate, $endDate];
        }

        if ($this->has('is_overdue')) {
            $rawValue = $this->input('is_overdue');

            if (in_array($rawValue, [true, false, 1, 0, '1', '0', 'true', 'false'], true)) {
                $data['is_overdue'] = $this->boolean('is_overdue');
            }
        }

        $this->merge($data);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string'],
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'is_overdue' => ['sometimes', 'boolean'],
            'created_at' => ['sometimes', 'array', 'size:2'],
            'created_at.0' => ['required_with:created_at', 'date'],
            'created_at.1' => ['required_with:created_at', 'date', 'after_or_equal:created_at.0'],

        ];
    }

    public function messages(): array
    {
        $statuses = implode(', ', array_column(TaskStatus::cases(), 'value'));
        $priorities = implode(', ', array_column(TaskPriority::cases(), 'value'));

        return [
            'status.enum' => "Status inválido. Valores aceitos: {$statuses}.",
            'priority.enum' => "Prioridade inválida. Valores aceitos: {$priorities}",
            'is_overdue.boolean' => 'O campo exige um valor booleano',
            'created_at.array' => 'O filtro de datas deve ser um array.',
            'created_at.size' => 'Informe exatamente uma data inicial e uma data final.',
            'created_at.*.date' => 'Cada item do filtro de datas deve ser uma data válida.',
        ];
    }
}
