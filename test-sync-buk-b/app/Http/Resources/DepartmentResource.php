<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray($request): array
    {
       return [
            'id'               => $this->id,
            'name'             => $this->name,
            'cost_center_code' => $this->cost_center_code,
            'created_at'       => optional($this->created_at)->toISOString(),
            'updated_at'       => optional($this->updated_at)->toISOString(),
        ];
    }
}
