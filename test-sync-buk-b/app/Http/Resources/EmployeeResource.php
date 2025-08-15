<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'email'        => $this->email,
            'position'     => $this->position,
            'hire_date'    => optional($this->hire_date)->toDateString(),
            'is_active'    => (bool) $this->is_active,
            'department_id'=> $this->department_id,
            'department'   => $this->whenLoaded('department', fn() => [
                'id'               => $this->department->id,
                'name'             => $this->department->name,
                'cost_center_code' => $this->department->cost_center_code,
            ]),
            'skills'       =>      $this->whenLoaded('skills', function () {
                return $this->skills->map(function ($s) {
                    $level = $s->pivot->level?? ($s->assignment->level ?? 0);
                    return [
                        'id'    => $s->id,
                        'name'  => $s->name,
                        'level' => (int) $level,
                    ];
                });
            }),
            'created_at'   => optional($this->created_at)->toISOString(),
            'updated_at'   => optional($this->updated_at)->toISOString(),
        ];
    }
}
