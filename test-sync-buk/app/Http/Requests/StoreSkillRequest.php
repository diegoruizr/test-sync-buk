<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSkillRequest extends FormRequest
{
    public function authorize(): bool {
        return true; 
    }

    public function rules(): array
    {
        return [
            'name'            => ['required','string','max:150'],
            'level_required'  => ['required','integer','min:0'],
        ];
    }
}
