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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "search"       => ["sometimes", "string"],
            "status"       => ["sometimes", Rule::enum(TaskStatus::class)],
            "priority"     => ["sometimes", Rule::enum(TaskPriority::class)],
            "is_overdue"   => ["sometimes", "in:true,false,1,0"],
            "created_at"   => ["sometimes", "array", "size:2"],
            "created_at.*" => ["date"]
        ];
    }

    public function messages(): array
    {
        $statuses   = implode(', ', array_column(TaskStatus::cases(), 'value'));
        $priorities = implode(', ', array_column(TaskPriority::cases(), 'value'));

        return [
            'status.enum'        => "Status inválido. Valores aceitos: {$statuses}.",
            'prioriry.enum'      => "Prioridade inválida. Valores aceitos: {$priorities}",
            'is_overdue.in'      => "O campo exige um valor booleano",
            'created_at.array'   => 'O filtro de datas deve ser um array.',
            'created_at.size'    => 'Informe exatamente uma data inicial e uma data final.',
            'created_at.*.date'  => 'Cada item do filtro de datas deve ser uma data válida.',
        ];
    }
}
