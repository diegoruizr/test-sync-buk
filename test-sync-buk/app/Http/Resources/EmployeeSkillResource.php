<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeSkillResource extends JsonResource
{
    public function toArray($request): array
    {
        $employeeId = $this->employee_id ?? $this->pivot?->employee_id;
        $skillId    = $this->skill_id    ?? $this->pivot?->skill_id;
        $level      = $this->level       ?? $this->pivot?->level;
        $updated    = $this->updated_at  ?? $this->pivot?->updated_at;
        $deleted    = $this->deleted_at  ?? $this->pivot?->deleted_at;

        return [
            'employee_id' => $employeeId,
            'skill_id'    => $skillId,
            'level'       => (int) $level,
            'updated_at'  => $updated?->format('Y-m-d\TH:i:s.uP'),
            'deleted_at'  => $deleted?->format('Y-m-d\TH:i:s.uP'),
        ];
    }
}
