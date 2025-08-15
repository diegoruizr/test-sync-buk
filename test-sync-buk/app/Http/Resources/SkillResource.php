<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SkillResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'level_required' => (int) $this->level_required,
            'created_at'     => $this->created_at?->format('Y-m-d\TH:i:s.uP'),
            'updated_at'     => $this->updated_at?->format('Y-m-d\TH:i:s.uP'),
            'deleted_at'     => $this->deleted_at?->format('Y-m-d\TH:i:s.uP'),
        ];
    }
}
