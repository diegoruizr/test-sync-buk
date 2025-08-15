<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
         return [
            'name'          => ['required','string','max:150'],
            'email'         => ['required','email','max:150', Rule::unique('employees','email')->whereNull('deleted_at'),],
            'position'      => ['nullable','string','max:150'],
            'hire_date'     => ['nullable','date'],
            'department_id' => ['required','uuid', Rule::exists('departments','id')->whereNull('deleted_at'),],
            'is_active'     => ['sometimes','boolean'],

            'skills'               => ['sometimes','array'],
            'skills.*.id'          => ['required','uuid','distinct', Rule::exists('skills','id')],
            'skills.*.level'       => ['required','integer','min:0'],
            'skills_strategy'      => ['sometimes','in:merge,replace'],
        ];
    }
}
