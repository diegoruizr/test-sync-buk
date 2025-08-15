<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'email'         => $this->email,
            'position'      => $this->position,
            'hire_date'     => optional($this->hire_date)->toDateString(),
            'department_id' => $this->department_id,
            'is_active'     => (bool) $this->is_active,
            'created_at'    => $this->created_at?->format('Y-m-d\TH:i:s.uP'),
            'updated_at'    => $this->updated_at?->format('Y-m-d\TH:i:s.uP'),
            'deleted_at'    => $this->deleted_at?->format('Y-m-d\TH:i:s.uP'),
            'skills' => $this->whenLoaded('skills', function () {
                return $this->skills->map(function ($s) {
                    $level = $s->pivot->level?? ($s->assignment->level ?? 0);
                    return [
                        'id'    => $s->id,
                        'name'  => $s->name,
                        'level' => (int) $level,
                    ];
                });
            }),
        ];
    }
}
