<?php

namespace App\Services\Employees;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class ApplyEmployeeSkills
{
    /**
     * @param Employee $employee
     * @param array<array{id:string,level:int}> $skills
     * @param 'merge'|'replace' $strategy
     */
    public function handle(Employee $employee, array $skills, string $strategy = 'replace'): void
    {
        DB::transaction(function () use ($employee, $skills, $strategy) {

            $incoming = collect($skills)->mapWithKeys(fn ($i) => [$i['id'] => (int) $i['level']]);

            $current = $employee->skills()
                ->withPivot(['level','deleted_at','created_at','updated_at'])
                ->get()
                ->keyBy('id');

            foreach ($incoming as $skillId => $level) {
                /** @var \App\Models\Skill|null $skill */
                $skill = $current->get($skillId);

                if ($skill) {
                    $pivot = $skill->assignment;

                    // restaurar si estaba borrado
                    if ($pivot->trashed()) {
                        $pivot->restore();
                    }

                    // actualizar level si cambió
                    if ((int) $pivot->level !== $level) {
                        $pivot->level = $level;
                        $pivot->save();
                    }
                } else {
                    // crear relación
                    $employee->skills()->syncWithoutDetaching([
                        $skillId => ['level' => $level, 'deleted_at' => null],
                    ]);
                }
            }

            // REPLACE: todo lo que no vino soft delete con eventos
            if ($strategy === 'replace') {
                $current->each(function ($skill) use ($incoming) {
                    $pivot = $skill->assignment;
                    if (is_null($pivot->deleted_at) && !$incoming->has($skill->id)) {
                        $pivot->delete();
                    }
                });
            }
        });
    }
}