<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSkillRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('skill');

        return [
            'name'            => ['sometimes','required','string','max:150'],
            'level_required'  => ['sometimes','required','integer','min:0'],
        ];
    }
}
