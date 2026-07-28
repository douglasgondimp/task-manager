<?php

namespace App\Http\Requests\Project;

use App\Enums\ProjectStatus;
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
            'search'   => ['sometimes', 'string'],
            'status'   => ['sometimes', Rule::enum(ProjectStatus::class)],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'cursor'   => ['sometimes', 'nullable', 'string'],
        ];
    }
}
