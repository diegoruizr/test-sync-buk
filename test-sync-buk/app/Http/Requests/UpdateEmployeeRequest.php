<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
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
        $id = $this->route('employee');
        return [
            'name'          => ['sometimes','required','string','max:150'],
            'email'         => ['sometimes','required','email','max:150',Rule::unique('employees','email')->whereNull('deleted_at')->ignore($id, 'id'),],
            'position'      => ['sometimes','nullable','string','max:150'],
            'hire_date'     => ['sometimes','nullable','date'],
            'department_id' => ['sometimes','required','uuid',Rule::exists('departments','id')->whereNull('deleted_at'),],
            'is_active'     => ['sometimes','boolean'],

            'skills'          => ['sometimes','array'],
            'skills.*.id'     => ['required_with:skills','uuid','distinct', Rule::exists('skills','id')],
            'skills.*.level'  => ['required_with:skills','integer','min:0'],
            'skills_strategy' => ['sometimes','in:merge,replace'],
        ];
    }
}
